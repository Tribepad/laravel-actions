<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Queue\SerializesModels as BaseSerializesModels;

trait SerializesModels
{
    use BaseSerializesModels {
        __serialize as protected serializeFromBaseSerializesModels;
        __unserialize as protected unserializeFromBaseSerializesModels;
    }

    public function __sleep()
    {
        $properties = $this->sleepFromBaseSerializesModels();

        array_walk($this->attributes, function (&$value) {
            $value = $this->getSerializedPropertyValue($value);
        });

        return array_values(array_diff($properties, [
            'request', 'runningAs', 'actingAs', 'errorBag', 'validator',
            'commandInstance', 'commandSignature', 'commandDescription',
            'getAttributesFromConstructor',
        ]));
    }

    public function __wakeup()
    {
        $this->wakeupFromBaseSerializesModels();

        array_walk($this->attributes, function (&$value) {
            $value = $this->getRestoredPropertyValue($value);
        });
    }

    public function __serialize()
    {
        array_walk($this->attributes, function (&$value) {
            $value = $this->getSerializedPropertyValue($value);
        });

        return $this->serializeFromBaseSerializesModels();
    }

    public function __unserialize(array $values)
    {
        $this->unserializeFromBaseSerializesModels($values);

        array_walk($this->attributes, function (&$value) {
            $value = $this->getRestoredPropertyValue($value);
        });
    }

        /**
     * @return array
     */
    protected function sleepFromBaseSerializesModels()
    {
        $properties = (new ReflectionClass($this))->getProperties();

        foreach ($properties as $property) {
            $property->setValue($this, $this->getSerializedPropertyValue(
                $this->getPropertyValue($property)
            ));
        }

        return array_values(array_filter(array_map(function ($p) {
            return $p->isStatic() ? null : $p->getName();
        }, $properties)));
    }

    /**
     * @return void
     */
    protected function wakeupFromBaseSerializesModels()
    {
        foreach ((new ReflectionClass($this))->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $property->setValue($this, $this->getRestoredPropertyValue(
                $this->getPropertyValue($property)
            ));
        }
    }
}
