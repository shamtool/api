<?php

/**
 * Serves as a layer to interact between an SQL table and a PHP entity.
 */
abstract class DatabaseEntity {
    /**
     * An associative array mapping database field name to class field name.
     * 
     * @var array<string>
     */
    protected $fieldDbToClass = [];
    /**
     * An associative array mapping class field name to database field name.
     * READ-ONLY: This is populated from `fieldDbToClass`.
     * 
     * @var array<string>
     */
    protected $fieldClassToDb = [];
    /**
     * An associative array mapping class field name to setter and getter callables.
     * Any class names here must map from an existing value in `fieldDbToClass`, and map to valid callables.
     * Each subarray should have both `get` and `set` properties defined.
     * 
     * @var array<callable>
     */
    protected $fieldClassToFn = [];
    /**
     * An associative array of class field names pending updates to the database.
     */
    protected $pendingUpdate = [];

    public function __construct() {
        foreach ($this->fieldDbToClass as $db_key => $class_key) {
            if (!(gettype($db_key) == "string" || gettype($db_key) === "integer") ||
                    !(gettype($class_key) == "string" || gettype($class_key) == "integer")) {
                $class_name = get_class($this);
                trigger_error("fieldDbToClass: Expected string values for '{$db_key}' of class '{$class_name}'\nStack trace:\n"
                    . (new Exception())->getTraceAsString(), E_USER_ERROR);
            }

            // Check if this is even a valid class field or defined callable
            if (!property_exists($this, $class_key) && !$this->fieldClassToFn[$class_key] ?? null) {
                $class_name = get_class($this);
                trigger_error("fieldDbToClass: The class field name defined does not exist for '{$db_key}' of class '{$class_name}'\nStack trace:\n"
                    . (new Exception())->getTraceAsString(), E_USER_ERROR);
            }

            // Make it a two-way map
            $this->fieldClassToDb[$class_key] = $db_key;
        }

        foreach ($this->fieldClassToFn as $class_key => $callables) {
            if (!$this->fieldClassToDb[$class_key]) {
                $class_name = get_class($this);
                trigger_error("fieldClassToFn: The class field name defined does not exist for '{$class_key}' of class '{$class_name}'\nStack trace:\n"
                    . (new Exception())->getTraceAsString(), E_USER_ERROR);
            }

            if (!is_callable($callables["get"]) || !is_callable($callables["set"])) {
                $class_name = get_class($this);
                trigger_error("fieldClassToFn: The getter and/or setter defined does not exist for '{$class_key}' of class '{$class_name}'\nStack trace:\n"
                    . (new Exception())->getTraceAsString(), E_USER_ERROR);
            }
        }
    }

    /**
     * Sets a property, whether it is binded to callables or not.
     */
    protected function setProp($propName, $value) {
        if ($callables = $this->fieldClassToFn[$propName] ?? null) {
            call_user_func($callables['set'], $value);
        } else {
            // Regular direct class field setting
            $this->$propName = $value;
        }
    }

    /**
     * Retrieves a property, whether it is binded to callables or not.
     */
    protected function getProp($propName) {
        if ($callables = $this->fieldClassToFn[$propName] ?? null) {
            return call_user_func($callables['get']) ?? null;
        } else {
            // Regular direct class field 
            return $this->$propName ?? null;
        }
    }

    /**
     * Updates a class property by name, staging the change for saving into the database.
     * 
     * @throws Exception On any error setting the property
     */
    public function update(string $propName, int|float|string|bool $value) : self {
        if (!$this->fieldClassToFn[$propName] ?? null) {
            $class_name = get_class($this);
            trigger_error("Tried to set a non-existent property '{$propName}' of class '{$class_name}'\nStack trace:\n"
                . (new Exception())->getTraceAsString(), E_USER_ERROR);
        }

        if ($value !== $this->getProp($propName)) {
            $this->pendingUpdate[$propName] = true;
            $this->setProp($propName, $value);
        }

        return $this;
    }

    /**
     * Imports an associative array to populate the object.
     * 
     * @param array $assoc The associative array.
     * @param bool? $log Whether to error-log any fields that were not set
     */
    protected function importAssoc($assoc, $log = true) {
        foreach ($assoc as $db_key => $value) {
            $prop_name = $this->fieldDbToClass[$db_key] ?? null;
            if ($prop_name == null) {
                $class_name = get_class($this);
                if ($log == true) {
                    error_log("The DB key '{$db_key}' does not have a class field mapping for class '{$class_name}'\nStack trace:\n"
                        . (new Exception())->getTraceAsString(), E_USER_ERROR);
                }
            } else {
                // Prop name should be safe as verified in the constructor
                $this->setProp($prop_name, $value);
            }
        }
    }

    /**
     * Saves changes, then loads.
     * Equivalent to calling `save()` then `load()`.
     */
    public function sync() {
        $this->save();
        $this->load();
    }

    /**
     * Loads from the database into the object.
     */
    abstract public function load();

    /**
     * Saves changes to the object into the database.
     */
    abstract public function save();
}

require_once __DIR__ . '/../STDatabase.php';

abstract class STDatabaseEntity extends DatabaseEntity {
    /** The SQL table name of the entity */
    protected string $tableName;
    protected bool $isLoaded = false;
    /** The class field name for the unique identifier */
    protected string $idPropName = "id";
    protected STDatabase $dbConn;

    public function __construct() {
        $class_name = get_class($this);

        // Make sure that the db table name is set
        if (!is_string($this->tableName)) {
            trigger_error("Database table name of class '{$class_name}' not defined\nStack trace:\n"
                . (new Exception())->getTraceAsString(), E_USER_ERROR);
        }

        parent::__construct();

        // Make sure ID exists
        if (($this->fieldClassToDb[$this->idPropName] ?? null) == null) {
            trigger_error("idPropName of class '{$class_name}' not defined in fieldClassToDb\nStack trace:\n"
                . (new Exception())->getTraceAsString(), E_USER_ERROR);
        }

        $this->dbConn = STDatabase::getInstance();
    }

    private function getInsertUpdateStmt() : ?PDOStatement {
        // First check if this record exists.
        $idPropName = $this->idPropName;
        $idFieldName = $this->fieldClassToDb[$idPropName];
        $idValue = $this->getProp($idPropName);

        $is_existing = false;
        if ($idValue) {
            // ID may be unsafe, protect it from SQL injection
            $statement = $this->dbConn->prepare("SELECT 1 FROM {$this->tableName} WHERE {$idFieldName} = ?");
            if ($statement == false) throw new Exception("Could not prepare SQL statement.");
            $statement->bindValue(1, $idValue);
            if (!$statement->execute()) throw new Exception("Could not execute SQL statement");

            $result = $statement->fetch(PDO::FETCH_NUM);
            $is_existing = $result ? $result[0] : false;
        }

        if ($is_existing) {
            // UPDATE
            $update_fields = [];
            foreach ($this->pendingUpdate as $class_key => $_) {
                $update_fields[] = "{$this->fieldClassToDb[$class_key]} = :{$class_key}";
            }

            if (count($update_fields) == 0) {
                // Nothing to update
                return null;
            }
            $update_fields_str = implode(',', $update_fields);

            // ID and field value may be unsafe, protect them from SQL injection
            $escaped_id_value = $this->dbConn->quote($idValue);
            $statement = $this->dbConn->prepare("UPDATE {$this->tableName} SET {$update_fields_str} WHERE {$idFieldName} = {$escaped_id_value}");
            if ($statement == false) throw new Exception("Could not prepare SQL statement.");

            foreach ($this->pendingUpdate as $class_key => $_) {
                if ($class_key == $idPropName) continue;
                $statement->bindValue(":{$class_key}", $this->getProp($class_key));
            }

            return $statement;
        } else {
            // INSERT
            $field_names = [];
            $field_value = [];
            foreach ($this->fieldDbToClass as $db_key => $class_key) {
                $field_names[] = $db_key;
                $field_value[] = ":{$class_key}";
            }
            $field_names_str = implode(',', $field_names);
            $field_value_str = implode(',', $field_value);

            // ID and field value may be unsafe, protect them from SQL injection
            $statement = $this->dbConn->prepare("INSERT INTO {$this->tableName} ({$field_names_str}) VALUES ({$field_value_str})");
            if ($statement == false) throw new Exception("Could not prepare SQL statement.");

            foreach ($this->fieldClassToDb as $class_key => $_) {
                $statement->bindValue(":{$class_key}", $this->getProp($class_key));
            }

            return $statement;
        }
    }

    /**
     * @throws Exception On failure (Invalid ID, connection failure, etc.)
     */
    public function save() {
        $statement = $this->getInsertUpdateStmt();
        if ($statement && !$statement->execute()) throw new Exception("SAVE: Could not execute SQL statement");
        $this->pendingUpdate = [];

        // If the ID is null, update it with a last inserted ID (else throw if there is no ID returned).
        $idPropName = $this->idPropName;
        if (!$this->getProp($idPropName)) {
            $last_id = $this->dbConn->lastInsertId();
            if (!$last_id) throw new Exception("SAVE: Attempted to save, but no ID was returned.");
            $this->setProp($idPropName, $last_id);
        }
    }

    /**
     * @throws Exception On failure (Invalid ID, connection failure, etc.)
     */
    public function load() {
        $idPropName = $this->idPropName;
        $idFieldName = $this->fieldClassToDb[$idPropName];
        $idValue = $this->getProp($idPropName);

        if (!$idValue) {
            $class_name = get_class($this);
            trigger_error("ID ({$idPropName}) of class '{$class_name}' not defined\nStack trace:\n"
                . (new Exception())->getTraceAsString(), E_USER_ERROR);
        }

        $fields_str = implode(',', array_keys($this->fieldDbToClass));

        // ID may be unsafe, protect it from SQL injection
        $statement = $this->dbConn->prepare("SELECT {$fields_str} FROM {$this->tableName} WHERE {$idFieldName} = ?");
        if ($statement == false) throw new Exception("LOAD: Could not prepare SQL statement.");
        $statement->bindValue(1, $idValue);

        if (!$statement->execute()) throw new Exception("LOAD: Could not execute SQL statement.");
        $assoc = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$assoc) throw new Exception("LOAD: Could not import data, does the ID ({$idValue}) exist?");
        $this->importAssoc($assoc);
    }

    /**
     * Checks if the ID exists in the corresponding database table.
     */
    public function idExists() : bool {
        // First check if this record exists.
        $idPropName = $this->idPropName;
        $idFieldName = $this->fieldClassToDb[$idPropName];
        $idValue = $this->getProp($idPropName);

        $is_existing = false;
        if ($idValue) {
            // ID may be unsafe, protect it from SQL injection
            $statement = $this->dbConn->prepare("SELECT 1 FROM {$this->tableName} WHERE {$idFieldName} = ?");
            if ($statement == false) return false;
            $statement->bindValue(1, $idValue);
            if (!$statement->execute()) return false;

            $result = $statement->fetch(PDO::FETCH_NUM);
            $is_existing = $result ? $result[0] : false;
        }

        return $is_existing;
    }
}

class Test  extends STDatabaseEntity{
    public string $name;
    public ?string $id = null;

    protected string $idPropName = "id";

    protected string $tableName = "all_mapsTest";

    protected $fieldDbToClass = [
        'author' => 'nama',
        'mapcode' => 'id'
    ];

    protected $fieldClassToFn = [
        'nama' => ['get' => 'getName', 'set' => 'setName'],
    ];

    public function getName() {
        return $this->name;
    }
    
    public function setName(string $name) {
        $this->name = $name;
    }
}

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment vars into memory
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'SECRET_PASS']);

$c = new Test();
$c->id = "@6969692";
$c->update("name", "GEKOOOOOOO");
$c->sync();

error_log("name is " . $c->nama);

$c->update("name", "GEKKEHYYY");
$c->sync();

//$c->update("name", "proooo");
//$c->importAssoc([22 => 69,"aaa" => "non", "dbNAMA" => "fkkkk"]);
error_log("name is " . $c->nama);
