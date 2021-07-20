<?php

namespace Oro\Bundle\DraftBundle\Voter;

use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Helper\DraftHelper;
use Oro\Bundle\DraftBundle\Helper\DraftPermissionHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Responsible for granting access to draft entities. Includes special rights for the draft owner.
 * Separates the rights of the owner of the draft and the rights of any draft.
 * Support permissions:
 * - View All Drafts
 * - Edit All Drafts
 * - Delete All Drafts
 * - View Own Draft (virtual permission)
 * - Edit Own Draft (virtual permission)
 * - Delete Own Drafts
 * - Create draft
 * - Publish draft
 *
 * Virtual permissions are more important than any permissions.
 */
class BasicPermissionsDraftVoter extends AbstractEntityVoter
{
    private const PERMISSION_CREATE = 'CREATE_DRAFT';
    private const PERMISSION_PUBLISH = 'PUBLISH_DRAFT';

    /**
     * @var array
     */
    protected $supportedAttributes = [
        BasicPermission::VIEW,
        BasicPermission::EDIT,
        BasicPermission::DELETE,
        self::PERMISSION_CREATE,
        self::PERMISSION_PUBLISH
    ];

    /**
     * @var DraftPermissionHelper
     */
    private $draftPermissionHelper;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        DraftPermissionHelper $draftPermissionHelper,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->draftPermissionHelper = $draftPermissionHelper;
        $this->authorizationChecker = $authorizationChecker;

        parent::__construct($doctrineHelper);
    }

    /**
     * @inheritDoc
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute): int
    {
        /** @var DraftableInterface $entity */
        $entity = $this->doctrineHelper->getEntity($class, $identifier);
        if (null === $entity) {
            return self::ACCESS_ABSTAIN;
        }

        if (!DraftHelper::isDraft($entity)) {
            return self::ACCESS_ABSTAIN;
        }

        switch ($attribute) {
            case BasicPermission::EDIT:
            case BasicPermission::VIEW:
                $result = $this->checkBasicPermission($entity, $attribute);
                break;
            case BasicPermission::DELETE:
                $result = $this->checkDeletePermission($entity, $attribute);
                break;
            case self::PERMISSION_CREATE:
            case self::PERMISSION_PUBLISH:
                $result =  $this->checkSourcePermission($entity, $attribute);
                break;
            default:
                $result = self::ACCESS_ABSTAIN;
                break;
        }

        return $result;
    }

    private function checkBasicPermission(DraftableInterface $object, string $attribute): int
    {
        if ($this->draftPermissionHelper->isUserOwned($object)) {
            return self::ACCESS_GRANTED;
        }
        $draftGlobalPermission = $this->draftPermissionHelper->generateGlobalPermission($attribute);

        return $this->isGranted($object, $draftGlobalPermission);
    }

    private function checkDeletePermission(DraftableInterface $object, string $attribute): int
    {
        $permission = $this->draftPermissionHelper->generatePermissions($object, $attribute);

        return $this->isGranted($object, $permission);
    }

    private function checkSourcePermission(DraftableInterface $object, string $attribute): int
    {
        $source = $object->getDraftSource();
        $permissions = $this->draftPermissionHelper->generatePermissions($object, BasicPermission::VIEW);

        return $this->isGranted($source, $attribute) | $this->isGranted($object, $permissions);
    }

    private function isGranted(DraftableInterface $object, string $attribute): int
    {
        return $this->authorizationChecker->isGranted($attribute, $object)
            ? self::ACCESS_GRANTED
            : self::ACCESS_DENIED;
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    protected function supportsClass($class): bool
    {
        return is_a($class, DraftableInterface::class, true);
    }
}
