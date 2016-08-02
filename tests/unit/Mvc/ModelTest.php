<?php

namespace Phalcon\Test\Unit\Mvc;

use Phalcon\Test\Models\Packages;
use Phalcon\Test\Module\UnitTest;
use Phalcon\Test\Models\PackageDetails;
use Phalcon\Mvc\Model\Resultset\Simple;
use Phalcon\Test\Models\AlbumORama\Albums;

/**
 * \Phalcon\Test\Unit\Mvc\Model\ManagerTest
 * Tests the Phalcon\Mvc\Model\Manager component
 *
 * @copyright (c) 2011-2016 Phalcon Team
 * @link      http://www.phalconphp.com
 * @author    Andres Gutierrez <andres@phalconphp.com>
 * @author    Serghei Iakovlev <serghei@phalconphp.com>
 * @author    Wojciech Ślawski <jurigag@gmail.com>
 * @package   Phalcon\Test\Unit\Mvc\Model
 *
 * The contents of this file are subject to the New BSD License that is
 * bundled with this package in the file docs/LICENSE.txt
 *
 * If you did not receive a copy of the license and are unable to obtain it
 * through the world-wide-web, please send an email to license@phalconphp.com
 * so that we can send you a copy immediately.
 */
class ModelTest extends UnitTest
{
    /**
     * @var \Phalcon\Mvc\Model\Manager
     */
    private $modelsManager;

    protected function _before()
    {
        parent::_before();
        /** @var \Phalcon\Mvc\Application $app */
        $app = $this->tester->getApplication();
        $this->modelsManager = $app->getDI()->getShared('modelsManager');
    }

    public function testCamelCaseRelation()
    {
        $this->specify(
            "CamelCase relation calls should be the same cache",
            function () {
                $this->modelsManager->registerNamespaceAlias('AlbumORama','Phalcon\Test\Models\AlbumORama');
                $album = Albums::findFirst();

                $album->artist->name = 'NotArtist';
                expect($album->artist->name)->equals($album->Artist->name);
            }
        );
    }

    /**
     * Tests find with empty conditions + bind and limit.
     *
     * @issue  11919
     * @author Serghei Iakovlev <serghei@phalconphp.com>
     * @since  2016-07-29
     */
    public function testEmptyConditions()
    {
        $this->specify(
            'The Model::find with empty conditions + bind and limit return wrong result',
            function () {
                $album = Albums::find([
                    'conditions' => '',
                    'bind'       => [],
                    'limit'      => 10
                ]);

                expect($album)->isInstanceOf(Simple::class);
                expect(ini_get('opcache.enable_cli'))->equals(1);

                expect($album->getFirst())->isInstanceOf(Albums::class);

                expect($album->getFirst()->toArray())->equals([
                    'id' => 1,
                    'artists_id' => 1,
                    'name' => 'Born to Die',
                ]);
            }
         );
    }

    /**
     * Tests Model::hasMany by using multi relation column
     *
     * @issue  12035
     * @author Serghei Iakovlev <serghei@phalconphp.com>
     * @since  2016-08-02
     */
    public function testMultiRelationColumn()
    {
        $this->specify(
            'The Model::hasMany by using multi relation column does not work as expected',
            function () {
                $list = Packages::find();
                foreach ($list as $item) {
                    expect($item)->isInstanceOf(Packages::class);
                    expect($item->details)->isInstanceOf(Simple::class);
                    expect($item->details->valid())->true();
                    expect($item->details->count())->greaterOrEquals(2);
                    expect($item->details->getFirst())->isInstanceOf(PackageDetails::class);
                }
            }
        );
    }
}
