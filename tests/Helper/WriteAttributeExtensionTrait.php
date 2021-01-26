<?php

namespace LevelCredit\Tradeline\Tests\Helper;

trait WriteAttributeExtensionTrait
{
    /**
     * @param string|object $classOrObject
     * @param string $attributeName
     * @param mixed $attributeValue
     * @throws \ReflectionException
     */
    protected function writeAttribute($classOrObject, string $attributeName, $attributeValue)
    {
        $rp = new \ReflectionProperty($classOrObject, $attributeName);
        $rp->setAccessible(true);
        $rp->setValue($classOrObject, $attributeValue);
        $rp->setAccessible(false);
    }
}
