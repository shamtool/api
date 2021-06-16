<?php

/**
 * Serves as a layer to interact between an SQL table and a PHP entity.
 */
abstract class DatabaseEntity {
    /**
     * An associative array mapping database field name to class field name.
     */
    protected $fieldDbToClass = [];
    /**
     * An associative array mapping class field name to database field name.
     * READ-ONLY: This is populated from `fieldDbToClass`.
     */
    protected $fieldClassToDb = [];
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

            // Check if this is even a valid class field
            if (!property_exists($this, $class_key)) {
                $class_name = get_class($this);
                trigger_error("fieldDbToClass: The class field name defined does not exist for '{$db_key}' of class '{$class_name}'\nStack trace:\n"
                    . (new Exception())->getTraceAsString(), E_USER_ERROR);
            }

            // Make it a two-way map
            $this->fieldClassToDb[$class_key] = $db_key;
        }
    }

    /**
     * Updates a class property by name, staging the change for saving into the database.
     * 
     * @throws Exception On any error setting the property
     */
    public function update(string $propName, int|float|string|bool $value) : self {
        if (!property_exists($this, $propName)) {
            $class_name = get_class($this);
            trigger_error("Tried to set a non-existent property '{$propName}' of class '{$class_name}'\nStack trace:\n"
                . (new Exception())->getTraceAsString(), E_USER_ERROR);
        }

        $pendingUpdate[$propName] = true;

        $this->$propName = $value;
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
                $this->$prop_name = $value;
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
    protected string $idClassName = "id";
    protected STDatabase $dbConn;

    public function __construct() {
        // Make sure that the db table name is set
        if (!is_string($this->tableName)) {
            $class_name = get_class($this);
            trigger_error("Database table name of class '{$class_name}' not defined\nStack trace:\n"
                . (new Exception())->getTraceAsString(), E_USER_ERROR);
        }

        parent::__construct();

        // Make sure ID exists
        if (($this->fieldClassToDb[$idClassName] ?? null) == null) {
            trigger_error("idClassName of class '{$class_name}' not defined in fieldClassToDb\nStack trace:\n"
                . (new Exception())->getTraceAsString(), E_USER_ERROR);
        }

        $this->dbConn = STDatabase::getInstance();
    }

    /**
     * Convenience function to generate a SELECT x FROM x WHERE x SQL statement.
     * 
     * @param string $idFieldName The DB field name of the unique identifier
     * @param mixed $idValue The ID to match
     */
    protected function getSelectFrom($idFieldName, $idValue) : PDOStatement {
        $fields_str = array_keys($this->fieldDbToClass).join(',');

        // ID may be unsafe, protect it from SQL injection
        $statement = $this->dbConn->prepare("SELECT {$fields_str} FROM {$this->tableName} WHERE {$idFieldName} = ? LIMIT = 1");
        if ($statement == false) throw new Exception("Could not prepare SQL statement.");
        $statement->bindValue(1, $idValue);

        return $statement;
    }

    /**
     * Convenience function to generate a INSERT/UPDATE SQL statement.
     * 
     * @param string $idFieldName The DB field name of the unique identifier
     * @param mixed $idValue The ID to match
     */
    protected function getUpdateInsert($idFieldName, $idValue) : PDOStatement {
        // First check if this record exists.

        // ID may be unsafe, protect it from SQL injection
        $statement = $this->dbConn->prepare("SELECT 1 FROM {$this->tableName} WHERE {$idFieldName} = ? LIMIT = 1");
        if ($statement == false) throw new Exception("Could not prepare SQL statement.");
        $statement->bindValue(1, $idValue);
        if (!$statement->execute()) throw new Exception("Could not execute SQL statement");
        
        $is_existing = $statement->fetch(PDO::FETCH_NUM)[0];

        if ($is_existing) {
            // UPDATE
            $update_fields = [];
            foreach ($this->pendingUpdate as $class_key) {
                $update_fields[] = "{$fieldClassToDb[$class_key]} = :{$class_key}";
            }
            
            // ID and field value may be unsafe, protect them from SQL injection
            $update_fields_str = update_fields.join(',');
            $statement = $this->dbConn->prepare("UPDATE {$this->tableName} SET {$update_fields_str} WHERE {$idFieldName} = ?");
            if ($statement == false) throw new Exception("Could not prepare SQL statement.");

            foreach ($this->pendingUpdate as $class_key) {
                if ($class_key == $idFieldName)
                $statement->bindValue(":{$class_key}", $this->$class_key);
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
            $field_names_str = $field_names.join(',');
            $field_value_str = $field_value.join(',');

            // ID and field value may be unsafe, protect them from SQL injection
            $statement = $this->dbConn->prepare("INSERT INTO {$this->tableName} ({$field_names_str}) VALUES (${$field_value_str})");
            if ($statement == false) throw new Exception("Could not prepare SQL statement.");

            foreach ($this->fieldClassToDb as $class_key) {
                $statement->bindValue(":{$class_key}", $this->$class_key);
            }

            return $statement;
        }
    }

    public function save() {
        $this->pendingUpdate = [];
    }

    public function load() {
    }
}

class Test  extends STDatabaseEntity{
    public string $name;
    public string $id;

    protected $tableName = "all_mapsTest";

    protected $fieldDbToClass = [
        'author' => 'name',
        'mapcode' => 'id'
    ];

    public function load() {
        $statement = $this->getSelectFrom('id', "@6244020");
        if (!$statement->execute()) throw new Exception("Could not execute SQL statement");
        $this->importAssoc($statement->fetch(PDO::FETCH_ASSOC));
    }

    public function save() {
        $this-
    }
}

$c = new Test();

$c->update("name", "proooo");
//$c->importAssoc([22 => 69,"aaa" => "non", "dbNAMA" => "fkkkk"]);
error_log("name is " . $c->name);
