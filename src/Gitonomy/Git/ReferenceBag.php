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

namespace Gitonomy\Git;

use Symfony\Component\Process\Process;

use Gitonomy\Git\Exception\ReferenceNotFoundException;

/**
 * Reference set associated to a repository.
 *
 * @author Alexandre Salomé <alexandre.salome@gmail.com>
 */
class ReferenceBag implements \Countable
{
    /**
     * Repository object.
     *
     * @var Gitonomy\Git\Repository
     */
    protected $repository;

    /**
     * Associative array of fullname references.
     *
     * @var array
     */
    protected $references;

    /**
     * List with all tags
     *
     * @var array
     */
    protected $tags;

    /**
     * List with all branches
     *
     * @var array
     */
    protected $branches;

    /**
     * A boolean indicating if the bag is already initialized.
     *
     * @var boolean
     */
    protected $initialized;

    /**
     * Constructor.
     *
     * @param Gitonomy\Git\Repository $repository The repository
     */
    public function __construct($repository)
    {
        $this->repository  = $repository;
        $this->initialized = false;
        $this->references  = array();
        $this->tags        = array();
        $this->branches    = array();
    }

    /**
     * Returns a reference, by name.
     *
     * @param string $fullname Fullname of the reference (refs/heads/master, for example).
     *
     * @return Gitonomy\Git\Reference A reference object.
     */
    public function get($fullname)
    {
        $this->initialize();

        if (!isset($this->references[$fullname])) {
            throw new ReferenceNotFoundException($fullname);
        }

        return $this->references[$fullname];
    }

    public function hasBranches()
    {
        $this->initialize();

        return count($this->branches) > 0;
    }

    public function getFirstBranch()
    {
        $this->initialize();
        reset($this->branches);

        return current($this->references);
    }

    public function resolveTags($hash)
    {
        $this->initialize();

        $tags = array();
        foreach ($this->references as $k => $reference) {
            if ($reference instanceof Reference\Tag && $reference->getCommitHash() === $hash)
            {
                $tags[] = $reference->getName();
            }
        }

        return $tags;
    }

    public function resolveBranches($hash)
    {
        $this->initialize();

        $tags = array();
        foreach ($this->references as $k => $reference) {
            if ($reference instanceof Reference\Branch && $reference->getCommitHash() === $hash)
            {
                $tags[] = $reference->getName();
            }
        }

        return $tags;
    }

    /**
     * Returns all tags.
     *
     * @return array
     */
    public function getTags()
    {
        $this->initialize();

        return $this->tags;
    }

    /**
     * Returns all branches.
     *
     * @return array
     */
    public function getBranches()
    {
        $this->initialize();

        $result = array();
        foreach ($this->references as $reference) {
            if ($reference instanceof Reference\Branch) {
                $result[] = $reference;
            }
        }

        return $result;
    }

    /**
     * Returns a given tag.
     *
     * @param string $name Name of the tag
     *
     * @return Gitonomy\Git\Reference\Tag
     */
    public function getTag($name)
    {
        $this->initialize();

        return $this->get('refs/tags/'.$name);
    }

    /**
     * Returns a given branch.
     *
     * @param string $name Name of the branch
     *
     * @return Gitonomy\Git\Reference\Branch
     */
    public function getBranch($name)
    {
        $this->initialize();

        return $this->get('refs/heads/'.$name);
    }

    protected function initialize()
    {
        if (true === $this->initialized) {
            return;
        }
        $this->initialized = true;

        $process = new Process('git show-ref --tags --heads');
        $process->setWorkingDirectory($this->repository->getPath());
        $process->run();

        $output = $process->getOutput();
        $error  = $process->getErrorOutput();

        if ($output === '' && $error !== '' || !$process->isSuccessFul() && $error !== '') {
            throw new \RuntimeException('Error while getting list of references');
        }

        $parser = new Parser\ReferenceParser();
        $parser->parse($output);

        foreach ($parser->references as $row) {
            list($commitHash, $fullname) = $row;

            if (preg_match('#^refs/heads/(.*)$#', $fullname, $vars)) {
                $reference = new Reference\Branch($this->repository, $fullname, $commitHash);
                $this->references[$fullname] = $reference;
                $this->branches[] = $reference;
            } elseif (preg_match('#^refs/tags/(.*)$#', $fullname, $vars)) {
                $reference = new Reference\Tag($this->repository, $fullname, $commitHash);
                $this->references[$fullname] = $reference;
                $this->tags[] = $reference;
            } else {
                throw new \RuntimeException(sprintf('Unable to parse "%s"', $fullname));
            }
        }
    }

    public function count()
    {
        return $this->references;
    }
}
