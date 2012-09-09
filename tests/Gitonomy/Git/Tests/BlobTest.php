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

use Gitonomy\Git\Blob;

class BlobTest extends TestBase
{
    const README_BLOB     = 'e43530af24200d2ba946db7e6a069899287ec772';
    const README_FRAGMENT = 'methods to access Git repository';

    public function testGetContent()
    {
        $repo = $this->getLibRepository();

        $blob = $repo->getBlob(self::README_BLOB);

        $this->assertContains(self::README_FRAGMENT, $blob->getContent());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetContentOnNotExisting()
    {
        $repo = $this->getLibRepository();

        $blob = $repo->getBlob("foobar");

        $blob->getContent();
    }
}
