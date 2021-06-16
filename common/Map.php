<?php

require_once __DIR__ . '/DatabaseEntity.php';

/** Represents a basic TFM map */
class CommonMap extends STDatabaseEntity {
    public ?int $id = null;
    public ?string $mapCode = null;
    public ?string $author = null;
    public ?string $xml = null;
    public ?int $wind = null;
    public ?int $gravity = null;
    public ?int $mgoc = null;
    public ?string $imageUrl = null;

    // DB properties
    protected string $tableName = "all_maps";
    protected string $idPropName = "id";
    protected $fieldDbToClass = [
        'id' => 'id',
        'mapcode' => 'mapCode',
        'author' => 'author',
        'xml' => 'xml',
        'wind' => 'wind',
        'gravity' => 'gravity',
        'mgoc' => 'mgoc',
        'image_url' => 'imageUrl',
    ];

    public function exportRESTObj() : array {
        $div_map = new DivinityMap($this);
        $div_map->id = $this->id;
        $spi_map = new SpiritualMap($this);
        $spi_map->id = $this->id;

        return [
            'id' => $this->id,
            'mapCode' => $this->mapCode,
            'author' => $this->author,
            'xml' => $this->xml,
            'wind' => $this->wind,
            'gravity' => $this->gravity,
            'mgoc' => $this->mgoc,
            'imageUrl' => $this->imageUrl,
            'isDivinity' => $div_map->idExists(),
            'isSpiritual' => $spi_map->idExists(),
        ];
    }
}

/** Represents a Divinity and/or Spiritual map */
abstract class SpiDivMap extends STDatabaseEntity {
    // TODO: this id needs to be kept in sync with CommonMap, perhaps make DBEntity support getting from callables?
    public ?int $id = null;
    public ?int $difficulty = null;
    public ?bool $cage = null;
    public ?bool $noAnchor = null;
    public ?bool $noMotor = null;
    public ?bool $water = null;
    public ?bool $timer = null;

    public CommonMap $commonMap;

    /**
     * NOTE: `id` is inferred from `commonMap`, and will need to be set manually when changed.
     */
    public function __construct(CommonMap $commonMap) {
        parent::__construct();
        $this->commonMap = $commonMap;
        $this->id = $commonMap->id;
    }

    public function save() {
        $this->commonMap->save();
        parent::save();
    }

    public function load() {
        $this->commonMap->load();
        parent::load();
    }

    public function exportRESTObj() : array {
        return [
            'id' => $this->id,
            'difficulty' => $this->difficulty,
            'cage' => $this->cage,
            'noAnchor' => $this->noAnchor,
            'noMotor' => $this->noMotor,
            'water' => $this->water,
            'timer' => $this->timer
        ];
    }
}

/** Represents a Divinity  map */
class DivinityMap extends SpiDivMap {
    public ?int $category = null;
    public ?bool $noBalloon = null;
    public ?bool $opportunist = null;

    // DB properties
    protected string $tableName = "mapdb_divinity";
    protected string $idPropName = "id";
    protected $fieldDbToClass = [
        'id' => 'id',
        'difficulty' => 'difficulty',
        'category' => 'category',
        'cage' => 'cage',
        'no_anchor' => 'noAnchor',
        'no_motor' => 'noMotor',
        'no_balloon' => 'noBalloon',
        'opportunist' => 'opportunist',
        'water' => 'water',
        'timer' => 'timer',
    ];

    public function exportRESTObj() : array {
        $base = parent::exportRESTObj();
        return array_merge($base, [
            'category' => $this->category,
            'noBalloon' => $this->noBalloon,
            'opportunist' => $this->opportunist,
        ]);
    }
}

/** Represents a Divinity and/or Spiritual map */
class SpiritualMap extends SpiDivMap {
    public ?bool $noB = null;

    // DB properties
    protected string $tableName = "mapdb_divinity";
    protected string $idPropName = "id";
    protected $fieldDbToClass = [
        'id' => 'id',
        'difficulty' => 'difficulty',
        'cage' => 'cage',
        'no_anchor' => 'noAnchor',
        'no_motor' => 'noMotor',
        'water' => 'water',
        'timer' => 'timer',
        'no_b' => 'noBalloon',
    ];
    
    public function exportRESTObj() : array {
        $base = parent::exportRESTObj();
        return array_merge($base, [
            'noB' => $this->noB,
        ]);
    }
}
