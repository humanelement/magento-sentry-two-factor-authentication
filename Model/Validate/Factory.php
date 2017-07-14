<?php
/**
 * Human Element Inc.
 *
 * @package HE_TwoFactorAuth
 * @copyright Copyright (c) 2017 Human Element Inc. (https://www.human-element.com)
 */

namespace HE\TwoFactorAuth\Model\Validate;

use Magento\Framework\ObjectManagerInterface;

/**
 * Message model factory
 */
class Factory
{
    /**
     * Allowed validator types
     *
     * @var string[]
     */
    protected $types = [
        ValidateInterface::TYPE_DISABLED,
        ValidateInterface::TYPE_DUO,
        ValidateInterface::TYPE_GOOGLE,
    ];

    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Factory constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create a two factor validate instance of a given type.
     *
     * @param string|null $type The provider type to create, must correspond to a provider type under the
     * namespace HE\TwoFactorAuth\Model\Validate\
     * @throws \InvalidArgumentException Exception gets thrown if type does not correspond to a valid Magento message
     * @return ValidateInterface
     */
    public function create($type)
    {
        if (!in_array($type, $this->types)) {
            throw new \InvalidArgumentException('Wrong validator type');
        }

        $className = 'HE\\TwoFactorAuth\Model\\Validate\\' . ucfirst($type);

        $validator = $this->objectManager->create($className);
        if (!$validator instanceof ValidateInterface) {
            throw new \InvalidArgumentException($className . ' doesn\'t implement \HE\TwoFactorAuth\Model\Validate\ValidateInterface');
        }

        return $validator;
    }
}
