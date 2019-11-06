<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Class DDC7079Test
 *
 * @group DDC-7079
 */
class DDC7079Test extends OrmFunctionalTestCase
{
    /** @var DefaultQuoteStrategy */
    private $strategy;

    /** @var AbstractPlatform */
    private $platform;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->platform = $this->_em->getConnection()->getDatabasePlatform();
        $this->strategy = new DefaultQuoteStrategy();
    }

    public function testGetTableName()
    {
        $table = [
            'name'=>'cms_user',
            'schema' => 'cms',
        ];

        $cm = $this->createClassMetadata(DDC7079CmsUser::class);
        $cm->setPrimaryTable($table);

        $this->assertEquals($this->getTableFullName($table), $this->strategy->getTableName($cm, $this->platform));
    }

    public function testJoinTableName()
    {
        $table = [
            'name'=>'cmsaddress_cmsuser',
            'schema' => 'cms',
        ];

        $cm = $this->createClassMetadata(DDC7079CmsAddress::class);
        $cm->mapManyToMany(
            [
                'fieldName'     => 'user',
                'targetEntity'  => 'DDC7079CmsUser',
                'inversedBy'    => 'users',
                'joinTable'     => $table,
            ]
        );

        $this->assertEquals($this->getTableFullName($table), $this->strategy->getJoinTableName($cm->associationMappings['user'], $cm, $this->platform));
    }

    private function getTableFullName(array $table) : string
    {
        $join = '.';
        if (! $this->platform->supportsSchemas() && $this->platform->canEmulateSchemas()) {
            $join = '__';
        }

        return $table['schema'] . $join . $table['name'];
    }

    private function createClassMetadata(string $className) : ClassMetadata
    {
        $cm = new ClassMetadata($className);
        $cm->initializeReflection(new RuntimeReflectionService());

        return $cm;
    }
}

/**
 * @Entity
 * @Table(name="cms_users", schema="cms")
 */
class DDC7079CmsUser
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /** @OneToOne(targetEntity="DDC7079CmsAddress", mappedBy="user", cascade={"persist"}, orphanRemoval=true) */
    public $address;
}

/**
 * @Entity
 * @Table(name="cms_addresses", schema="cms")
 */
class DDC7079CmsAddress
{
    /**
     * @Column(type="integer")
     * @Id @GeneratedValue
     */
    public $id;

    /**
     * @OneToOne(targetEntity="DDC7079CmsUser", inversedBy="address")
     * @JoinColumn(referencedColumnName="id")
     */
    public $user;
}
