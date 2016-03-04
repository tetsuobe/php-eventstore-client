<?php

namespace EventStore\ValueObjects\Identity;

use EventStore\ValueObjects\Exception\InvalidNativeArgumentException;
use EventStore\ValueObjects\StringLiteral\StringLiteral;
use EventStore\ValueObjects\Util\Util;
use EventStore\ValueObjects\ValueObjectInterface;
use Ramsey\Uuid\Uuid as BaseUUid;

class UUID extends StringLiteral
{
    /** @var BaseUuid */
    protected $value;

    /**
     * @return UUID
     * @internal param string $uuid
     */
    public static function fromNative()
    {
        $uuid_str = \func_get_arg(0);
        $uuid     = new static($uuid_str);

        return $uuid;
    }

    /**
     * Generate a new UUID string
     *
     * @return string
     */
    public static function generateAsString()
    {
        $uuid       = new static();
        $uuidString = $uuid->toNative();

        return $uuidString;
    }

    public function __construct($value = null)
    {
        $uuid_str = BaseUuid::uuid4();

        if (null !== $value) {
            $pattern = '/'.BaseUuid::VALID_PATTERN.'/';

            if (! \preg_match($pattern, $value)) {
                throw new InvalidNativeArgumentException($value, array('UUID string'));
            }

            $uuid_str = $value;
        }

        $this->value = \strval($uuid_str);
    }

    /**
     * Tells whether two UUID are equal by comparing their values
     *
     * @param  UUID $uuid
     * @return bool
     */
    public function sameValueAs(ValueObjectInterface $uuid)
    {
        if (false === Util::classEquals($this, $uuid)) {
            return false;
        }

        return $this->toNative() === $uuid->toNative();
    }
}
