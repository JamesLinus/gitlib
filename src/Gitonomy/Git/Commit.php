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

/**
 * Representation of a Git commit.
 *
 * @author Alexandre Salomé <alexandre.salome@gmail.com>
 */
class Commit
{
    /**
     * The repository associated to the commit.
     *
     * @var Gitonomy\Git\Repository
     */
    private $repository;

    /**
     * Hash of the commit.
     *
     * @var string
     */
    private $hash;

    /**
     * A flag indicating if the commit is initialized.
     *
     * @var boolean
     */
    private $initialized;

    /**
     * Hash of the tree.
     *
     * @var string
     */
    private $treeHash;
    private $tree;

    /**
     * Hashes of the parent commits.
     *
     * @var array
     */
    private $parentHashes;

    /**
     * Author name.
     *
     * @var string
     */
    private $authorName;

    /**
     * Author email.
     *
     * @var string
     */
    private $authorEmail;

    /**
     * Date of authoring.
     *
     * @var DateTime
     */
    private $authorDate;

    /**
     * Committer name.
     *
     * @var string
     */
    private $committerName;

    /**
     * Committer email.
     *
     * @var string
     */
    private $committerEmail;

    /**
     * Date of commit.
     *
     * @var DateTime
     */
    private $committerDate;

    /**
     * Message of the commit.
     *
     * @var string
     */
    private $message;

    /**
     * Short message of the commit.
     *
     * @var string
     */
    private $shortMessage;

    /**
     * Constructor.
     *
     * @param Gitonomy\Git\Repository $repository Repository of the commit
     *
     * @param string $hash Hash of the commit
     */
    public function __construct(Repository $repository, $hash)
    {
        $this->repository = $repository;
        $this->hash = $hash;
        $this->initialized = false;
    }

    /**
     * Initializes the commit, which means read data about it and fill object.
     *
     * @throws RuntimeException An error occurred during read of data.
     */
    private function initialize()
    {
        if (true === $this->initialized) {
            return;
        }

        ob_start();
        system(sprintf(
            'cd %s && git cat-file commit %s',
            escapeshellarg($this->repository->getPath()),
            escapeshellarg($this->hash)
        ), $return);
        $result = ob_get_clean();

        if (0 !== $return) {
            throw new \RuntimeException('Error while getting content of a commit');
        }

        $parser = new Parser\CommitParser();
        $parser->parse($result);

        $this->treeHash       = $parser->tree;
        $this->parentHashes   = $parser->parents;
        $this->authorName     = $parser->authorName;
        $this->authorEmail    = $parser->authorEmail;
        $this->authorDate     = $parser->authorDate;
        $this->committerName  = $parser->committerName;
        $this->committerEmail = $parser->committerEmail;
        $this->committerDate  = $parser->committerDate;
        $this->message        = $parser->message;

        $this->initialized = true;
    }

    public function getDiff()
    {
        return new Diff($this->repository, $this->hash);
    }

    /**
     * Returns the commit hash.
     *
     * @return string A SHA1 hash
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Returns parent hashes.
     *
     * @return array An array of SHA1 hashes
     */
    public function getParentHashes()
    {
        $this->initialize();

        return $this->parentHashes;
    }

    /**
     * Returns the parent commits.
     *
     * @return array An array of Commit objects
     */
    public function getParents()
    {
        $this->initialize();

        $result = array();

        foreach ($this->parentHashes as $parentHash) {
            $result[] = $this->repository->getCommit($parentHash);
        }

        return $result;
    }

    /**
     * Returns the tree hash.
     *
     * @return string A SHA1 hash
     */
    public function getTreeHash()
    {
        $this->initialize();

        return $this->treeHash;
    }

    public function getTree()
    {
        $this->initialize();

        if (null === $this->tree) {
            $this->tree = $this->repository->getTree($this->treeHash);
        }

        return $this->tree;
    }

    /**
     * @return Commit
     */
    public function getLastModification($path, $lastHash = null)
    {
        if (preg_match('#^/#', $path)) {
            $path = substr($path, 1);
        }
        ob_start();
        $cmd = sprintf(
            'cd %s && git log --format="format:%%H" -n 1 %s -- %s',
            escapeshellarg($this->repository->getPath()),
            escapeshellarg($this->hash),
            escapeshellarg($path)
        );
        system($cmd, $result);
        $output = ob_get_clean();
        if (0 != $result) {
            throw new \InvalidArgumentException('Error running '.$cmd);
        }

        return $this->repository->getCommit($output);
    }

    /**
     * Returns the first line of the commit, and the first 80 characters.
     *
     * @return string
     */
    public function getShortMessage()
    {
        $this->initialize();

        if (null !== $this->shortMessage) {
            return $this->shortMessage;
        }

        $pos    = mb_strpos($this->message, "\n");
        $length = mb_strlen($this->message);

        if (false === $pos) {
            if ($length < 80) {
                $shortMessage = $this->message;
            } else {
                $shortMessage = mb_substr($this->message, 0, 80).'...';
            }
        } else {
            if ($pos < 80) {
                $shortMessage = mb_substr($this->message, 0, $pos);
            } else {
                $shortMessage = mb_substr($this->message, 0, 80).'...';
            }
        }

        return $this->shortMessage = $shortMessage;
    }

    /**
     * Returns the author name.
     *
     * @return string A name
     */
    public function getAuthorName()
    {
        $this->initialize();

        return $this->authorName;
    }

    /**
     * Returns the author email.
     *
     * @return string An email
     */
    public function getAuthorEmail()
    {
        $this->initialize();

        return $this->authorEmail;
    }

    /**
     * Returns the authoring date.
     *
     * @return DateTime A time object
     */
    public function getAuthorDate()
    {
        $this->initialize();

        return $this->authorDate;
    }

    /**
     * Returns the committer name.
     *
     * @return string A name
     */
    public function getCommitterName()
    {
        $this->initialize();

        return $this->committerName;
    }

    /**
     * Returns the comitter email.
     *
     * @return string An email
     */
    public function getCommitterEmail()
    {
        $this->initialize();

        return $this->committerEmail;
    }

    /**
     * Returns the authoring date.
     *
     * @return DateTime A time object
     */
    public function getCommitterDate()
    {
        $this->initialize();

        return $this->committerDate;
    }

    /**
     * Returns the message of the commit.
     *
     * @return string A commit message
     */
    public function getMessage()
    {
        $this->initialize();

        return $this->message;
    }
}
