<?php
namespace dicr\cache;

use dicr\helper\ArrayHelper;
use yii\caching\Cache;

/**
 * Кэш в памяти в виде дерева.
 * Можно получить всю ветку значений.
 * 
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2018
 */
class TreeCache extends Cache {
	
	/**
	 * @inheritdoc
	 */
	public $serializer = false;

	/** @var array */
	private $_cache = [];
	
	/**
	 * {@inheritDoc}
	 * 
	 * @return array
	 * @see \yii\caching\Cache::buildKey()
	 */
	public function buildKey($key) {
		$key = array_merge($this->keyPrefix ?? [], (array)$key);
		$key = array_map(function($item) {
			if (is_null($item))	return 'null';
			if ($item === '') return '';
			if (is_numeric($item)) return (string)$item;
			if (is_bool($item)) return $item ? 'true' : 'false';
			if (!is_string($item)) $item = json_encode($item, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
			return strlen($item) <= 32 ? $item : md5($item);
		}, $key);
		return json_encode($key, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
	}
	
	/**
	 * Распаковывает ключ перед использованием в ArrayHelper
	 * 
	 * @param string $key массив в формате json
	 * @return array
	 */
	protected function unpackKey(string $key) {
		return json_decode($key, true);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getValue($key) {
		return ArrayHelper::getValue($this->_cache, $this->unpackKey($key)) ?? false;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function setValue($key, $value, $duration) {
		ArrayHelper::setValue($this->_cache, $this->unpackKey($key), $value);
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function addValue($key, $value, $duration) {
		return $this->exists($key) ? false : $this->setValue($key, $value, $duration);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function deleteValue($key) {
		ArrayHelper::remove($this->_cache, $this->unpackKey($key));
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function flushValues() {
		$this->_cache = [];
		return true;
	}
}
