<?php
declare(strict_types=1);
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.4
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Type;

use Cake\Database\Driver;
use InvalidArgumentException;
use PDO;
use RuntimeException;

/**
 * Decimal type converter.
 *
 * Use to convert decimal data between PHP and the database types.
 */
class DecimalType extends BaseType implements BatchCastingInterface
{
    /**
     * The class to use for representing number objects
     *
     * @var string
     */
    public static $numberClass = 'Cake\I18n\Number';

    /**
     * Whether numbers should be parsed using a locale aware parser
     * when marshalling string inputs.
     *
     * @var bool
     */
    protected $_useLocaleParser = false;

    /**
     * Convert integer data into the database format.
     *
     * @param string|int|float $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return string|null
     * @throws \InvalidArgumentException
     */
    public function toDatabase($value, Driver $driver): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_scalar($value)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot convert value of type `%s` to a decimal',
                getTypeName($value)
            ));
        }
        if (is_string($value) && is_numeric($value)) {
            return $value;
        }

        return sprintf('%F', $value);
    }

    /**
     * Convert float values to PHP floats
     *
     * @param null|string|resource $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return float|null
     * @throws \Cake\Core\Exception\Exception
     */
    public function toPHP($value, Driver $driver): ?float
    {
        if ($value === null) {
            return $value;
        }

        return (float)$value;
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function manyToPHP(array $values, array $fields, Driver $driver): array
    {
        foreach ($fields as $field) {
            if (!isset($values[$field])) {
                continue;
            }

            $values[$field] = (float)$values[$field];
        }

        return $values;
    }

    /**
     * Get the correct PDO binding type for integer data.
     *
     * @param mixed $value The value being bound.
     * @param \Cake\Database\Driver $driver The driver.
     * @return int
     */
    public function toStatement($value, Driver $driver): int
    {
        return PDO::PARAM_STR;
    }

    /**
     * Marshalls request data into PHP floats.
     *
     * @param mixed $value The value to convert.
     * @return mixed Converted value.
     */
    public function marshal($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value) && $this->_useLocaleParser) {
            return $this->_parseValue($value);
        }
        if (is_numeric($value)) {
            return (float)$value;
        }

        return null;
    }

    /**
     * Sets whether or not to parse numbers passed to the marshal() function
     * by using a locale aware parser.
     *
     * @param bool $enable Whether or not to enable
     * @return $this
     */
    public function useLocaleParser(bool $enable = true)
    {
        if ($enable === false) {
            $this->_useLocaleParser = $enable;

            return $this;
        }
        if (static::$numberClass === 'Cake\I18n\Number' ||
            is_subclass_of(static::$numberClass, 'Cake\I18n\Number')
        ) {
            $this->_useLocaleParser = $enable;

            return $this;
        }
        throw new RuntimeException(
            sprintf('Cannot use locale parsing with the %s class', static::$numberClass)
        );
    }

    /**
     * Converts a string into a float point after parsing it using the locale
     * aware parser.
     *
     * @param string $value The value to parse and convert to an float.
     * @return float
     */
    protected function _parseValue(string $value): float
    {
        /* @var \Cake\I18n\Number $class */
        $class = static::$numberClass;

        return $class::parseFloat($value);
    }
}