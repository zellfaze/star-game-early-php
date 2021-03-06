<?php
/*
Copyright (c) 2016 "Derric Atzrott"

This file is part of Star-Game.

Star-Game is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/


log_message('debug', 'Loaded CelestialObject classes');
abstract class CelestialObject implements SaveLoad {
  protected $id, $size, $starsystem, $position, $data;
  protected $ci;
  
  public function __construct($size, $isID = false) {
    $this->ci =& get_instance();
    
    if ($isID) {
      $this->id =  $size;
      $this->loadData();
    } else {
      $this->data['size'] = $size;
      $this->id = null;
      $this->starsystem = null;
      $this->position = null;
    }
  }
  
  public function outputSize() {
    return $this->data['size'];
  }
  
  abstract function outputType();
  
  public function setSystem($id) {
    $this->starsystem = $id;
  }
  
  public function setPosition($position) {
    $this->position = $position;
  }
  
  public function getPosition() {
    return $this->position;
  }
  
  public function &getSystem() {
    if ($this->starsystem === null) {
      return null;
    } else {
      $starSystem = new StarSystem($this->starsystem);
      return $starSystem;
    }
  }
  
  public function saveData() {
    if (($this->starsystem === null) || ($this->position === null)) {
      throw new Exception('Planet must be part of a star system');
    }
    $this->ci->load->model('celestialobjects_model');
    $data = $this->data;
    $data = json_encode($data);
    $saveData = array();
    $saveData['type'] = get_class($this);
    $saveData['system'] = $this->starsystem;
    $saveData['position'] = $this->position;
    $saveData['data'] = $data;
    
    if ($this->id == 0) {
      $this->id = $this->ci->celestialobjects_model->insertObject($saveData);
    } else {  
      $this->ci->celestialobjects_model->saveObject($this->id, $saveData);
    }
  }
  
  public function loadData() {
    $this->ci->load->model('celestialobjects_model');
    $planetData = $this->ci->celestialobjects_model->getObject($this->id);
    
    $this->starsystem = $planetData['system'];
    $this->position = $planetData['position'];
    
    $jsonData = json_decode($planetData['data'], true);
    $this->data = $jsonData;
  }
  
  public function getID() {
    return $this->id;
  }
  
  public function getData() {
    return $this->data;
  }
  
  public static function loadObject($id) {
    $ci = get_instance();
    
    $ci->load->model('celestialobjects_model');
    $planetData = $ci->celestialobjects_model->getObject($id);
    
    $planetObj = new $planetData['type']($planetData['id'], true);
    return $planetObj;
  }
}

//------------------------------------
//             Stars
//------------------------------------

class Star extends CelestialObject {
  
  public function outputType() {
    if ($this->data['size'] < 3329000) {
      return 'Dwarf Star';
    } elseif ($this->data['size'] < 53264000) {
      return 'Medium Star';
    } elseif ($this->data['size'] < 79896000) {
      return 'Giant Star';
    } elseif ($this->data['size'] < 133160000) {
      return 'Super Giant Star';
    } else {
      return 'Hyper Giant Star';
    }
  }
}

//------------------------------------
//              Planets
//------------------------------------

abstract class Planet extends CelestialObject {
  
  public function outputType() {
    return 'Planet';
  }
  
  abstract public function isHabitable();
}

class GasGiant extends Planet {
  
  public function outputType() {
    return 'Gas Giant';
  }
  
  public function isHabitable() {
    return false;
  }
}

class IceGiant extends Planet {
  
  public function outputType() {
    return 'Ice Giant';
  }
  
  public function isHabitable() {
    return false;
  }
}

class RockyPlanet extends Planet {
  
  public function __construct($size, $isID = false) {
    parent::__construct($size, $isID);
    $this->ci->load->model('settlement_model');
    
    //Have we ever determined if this planet is habitable?
    if (!isset($this->data['habitable'])) {
      if (rand(1,5) == 1) {
        $this->data['habitable'] = true;
      } else {
        $this->data['habitable'] = false;
      }
      
      if ($isID == true) {
        $this->saveData();
      }
    }
  }
  
  public function outputType() {
    return 'Rocky Planet';
  }
  
  public function isHabitable() {
    return $this->data['habitable'];
  }
  
  public function getSettlements() {
    if ($this->id === null) {
      throw new Exception('Planet must be saved before settlements can be used!');
    }
    
    $settlements = $this->settlement_model->getSettlementsOnPlanet($this->id);
    
    if ($settlements === null) {
      return null;
    }
    
    $settlementObjs = array();
    foreach ($settlements as $id) {
      $settlementObjs[] = new Settlement($id);
    }
    
    return $settlementObjs;
  }
  
  public function addSettlement($owner_id) {
    if ($this->id === null) {
      throw new Exception('Planet must be saved before settlements can be used!');
    }
    
    $settlement = new Settlement();
    $settlement->setOwner($owner_id);
    $settlement->setPlanet($this->id);
    $settlement->saveData();
    
    return $settlement;
  }
}

class Belt extends Planet {
  
  public function outputType() {
    return 'Belt';
  }
  
  public function isHabitable() {
    return false;
  }
}

//------------------------------------
//          Small Bodies
//------------------------------------

class SmallBody extends CelestialObject {
  public function outputType() {
    return 'Small Body';
  }
}

class Asteroid extends SmallBody {
  public function outputType() {
    return 'Asteroid';
  }
}

class Comet extends SmallBody {
  public function outputType() {
    return 'Comet';
  }
}