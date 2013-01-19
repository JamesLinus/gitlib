<?php

/**
 * This file is part of Gitonomy.
 *
 * (c) Alexandre Salomé <alexandre.salome@gmail.com>
 * (c) Julien DIDIER <genzo.wm@gmail.com>
 *
 * This source file is subject to the GPL license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gitonomy\Git\Tests;

use Gitonomy\Git\Admin;
use Gitonomy\Git\Repository;

class AdminTest extends AbstractTest
{
    private $tmpDir;

    public function setUp()
    {
        $this->tmpDir = self::createTempDir();
    }

    public function tearDown()
    {
        $this->deleteDir(self::createTempDir());
    }

    public function testBare()
    {
        $repository = Admin::init($this->tmpDir);

        $objectDir = $this->tmpDir.'/objects';

        $this->assertTrue($repository->isBare(), "Repository is bare");
        $this->assertTrue(is_dir($objectDir),     "objects/ folder is present");
        $this->assertTrue($repository instanceof Repository, "Admin::init returns a repository");
        $this->assertEquals($this->tmpDir, $repository->getGitDir(), "The folder passed as argument is git dir");
        $this->assertNull($repository->getWorkingDir(), "No working dir in bare repository");
    }

    public function testNotBare()
    {
        $repository = Admin::init($this->tmpDir, false);

        $objectDir = $this->tmpDir.'/.git/objects';

        $this->assertFalse($repository->isBare(), "Repository is not bare");
        $this->assertTrue(is_dir($objectDir), "objects/ folder is present");
        $this->assertTrue($repository instanceof Repository, "Admin::init returns a repository");
        $this->assertEquals($this->tmpDir.'/.git', $repository->getGitDir(), "git dir as subfolder of argument");
        $this->assertEquals($this->tmpDir, $repository->getWorkingDir(), "working dir present in bare repository");
    }

    /**
     * @dataProvider provideFoobar
     */
    public function testClone($repository)
    {
        $newDir = self::createTempDir();
        $new = $repository->cloneTo($newDir, $repository->isBare());
        self::registerDeletion($new);

        $oldRefs = $repository->getReferences()->getAll();
        $newRefs = $new->getReferences()->getAll();

        $this->assertEquals(array_keys($oldRefs), array_keys($newRefs), "same references in both repositories");

        if ($repository->isBare()) {
            $this->assertEquals($newDir, $new->getGitDir());
        } else {
            $this->assertEquals($newDir.'/.git', $new->getGitDir());
            $this->assertEquals($newDir, $new->getWorkingDir());
        }
    }

    /**
     * @expectedException RuntimeException
     */
    public function testExistingFile()
    {
        $file = $this->tmpDir.'/test';
        touch($file);

        Admin::init($file);
    }
}
