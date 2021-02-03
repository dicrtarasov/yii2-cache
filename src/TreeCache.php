<?php
/*
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license Apache-2.0
 * @version 03.02.21 20:42:02
 */

declare(strict_types = 1);
namespace dicr\cache;

use dicr\helper\ArrayHelper;
use Exception;
use yii\caching\Cache;

use function is_bool;
use function strlen;

/**
 * Кэш в памяти в виде дерева.
 * Можно получить всю ветку значений.
 *
 * @noinspection MissingPropertyAnnotationsInspection
 */
class TreeCache extends Cache
{
    /** @inheritDoc */
    public $serializer = false;

    /** @var array */
    private $_cache = [];

    /**
     * {@inheritdoc}
     */
    public function buildKey($key): string
    {
        $key = array_merge($this->keyPrefix ? (array)$this->keyPrefix : [], (array)$key);

        // каждый элемент ключа переводим в строку
        $key = array_map(static function($item) {
            if ($item === null) {
                return 'null';
            }

            if ($item === '') {
                return '';
            }

            if (is_bool($item)) {
                return $item ? 'true' : 'false';
            }

            $item = is_scalar($item) ? (string)$item : serialize($item);

            if (strlen($item) > 32) {
                $item = md5($item);
            }

            return $item;
        }, $key);

        // возвращаем строку ключа
        return serialize($key);
    }

    /**
     * Распаковывает ключ перед использованием в ArrayHelper
     *
     * @param string $key массив в формате json
     * @return array
     */
    protected function unpackKey(string $key): array
    {
        return unserialize($key, false);
    }

    /**
     * @inheritdoc
     * @return mixed
     * @throws Exception
     */
    protected function getValue($key)
    {
        return ArrayHelper::getValue($this->_cache, $this->unpackKey($key), false);
    }

    /**
     * {@inheritdoc}
     */
    protected function setValue($key, $value, $duration): bool
    {
        ArrayHelper::setValue($this->_cache, $this->unpackKey($key), $value);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function addValue($key, $value, $duration): bool
    {
        return $this->exists($key) ? false : $this->setValue($key, $value, $duration);
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteValue($key): bool
    {
        ArrayHelper::remove($this->_cache, $this->unpackKey($key));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function flushValues(): bool
    {
        $this->_cache = [];

        return true;
    }

    /**
     * Загружает содержимое данными
     *
     * @param array $content
     */
    public function load(array $content): void
    {
        $this->_cache = $content;
    }

    /**
     * Возвращает содержимое
     *
     * @return array
     */
    public function save(): array
    {
        return $this->_cache;
    }
}
