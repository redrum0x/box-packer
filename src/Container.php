<?php

namespace NAWebCo\BoxPacker;

class Container
{
    use ExtensionTrait;
    use DescribableTrait;

    /**
     * @var ContainerLevel[]
     */
    protected $lowerLevels = [];

    /**
     * @var float
     */
    protected $completeLevelsTotalHeight = 0.0;

    /**
     * @var ContainerLevel
     */
    protected $topLevel;

    /**
     * SolidContainer constructor.
     * @param float $width
     * @param float $length
     * @param float $height
     * @param string $description
     */
    public function __construct($width, $length, $height = 0.0, $description = '')
    {
        $this->setDimensions($width, $length, $height);
        $this->applyStandardOrientation();

        if( $this->getHeight() <= 0 ){
            throw new \InvalidArgumentException('Invalid dimensions provided. All dimensions must be greater than zero.');
        }

        $this->setDescription($description);
        $this->initNewTopLevel();
    }

    /**
     * @param $height
     * @return $this
     */
    public function setHeight($height)
    {
        if ($height < 0) {
            throw new \InvalidArgumentException(sprintf('Height must be greater than or equal to zero. %f given.', $height));
        }
        $this->height = (float)$height;
        return $this;
    }

    /**
     * @param Solid $solid
     * @return bool
     */
    public function addSolid(Solid $solid)
    {
        if (!$this->attemptToAddSolidToLowerLevels($solid)) {
            if (!$this->topLevel->addSolid($solid)) {
                if ($this->topLevel->getContentsCount() == 0) {
                    return false;
                }

                // Add another level and try again.
                $this->addNewLevel();
                if( !$this->topLevel->addSolid($solid)){
                    return false;
                }
            }
        }

        return $this->getContentsTotalHeight() <= $this->getHeight();
    }

    public function reset()
    {
        $this->lowerLevels = [];
        $this->completeLevelsTotalHeight = 0.0;
        $this->topLevel = null;
        $this->initNewTopLevel();
    }

    /**
     * @param Solid $solid
     * @return bool
     */
    protected function attemptToAddSolidToLowerLevels(Solid $solid)
    {
        foreach ($this->lowerLevels as $level) {
            if ($level->addSolid($solid)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return $this
     */
    public function sortLowerLevels()
    {
        usort($this->lowerLevels, function ($a, $b) {
            /** @var ContainerLevel $a */
            /** @var ContainerLevel $b */
            if ($a->getContentsMaxHeight() === $b->getContentsMaxHeight()) {
                return 0;
            }

            return $a->getContentsMaxHeight() < $b->getContentsMaxHeight() ? -1 : 1;
        });
        return $this;
    }

    /**
     * @return $this
     */
    public function addNewLevel()
    {
        $this->lowerLevels[] = $this->topLevel;
        $this->completeLevelsTotalHeight += $this->topLevel->getContentsMaxHeight();
        $this->initNewTopLevel();
        return $this;
    }

    /**
     * Initialize the prototype with the right dimension as the top level).
     * @return $this
     */
    protected function initNewTopLevel()
    {
        $this->topLevel = clone $this->levelPrototype;
        $this->topLevel->setDimensionsBySolid($this->getDimensionsAsSolid());
        return $this;
    }

    /**
     * @return array
     */
    public function getLevels()
    {
        return array_merge( $this->getLowerLevels(), [$this->topLevel]);
    }

    /**
     * @return array
     */
    protected function getLowerLevels()
    {
        return $this->lowerLevels;
    }

    /**
     * @return int
     */
    public function getContentsCount()
    {
        $count = 0;
        foreach ($this->lowerLevels as $level) {
            /** @var ContainerLevel $level */
            $count += $level->getContentsCount();
        }
        return $count + $this->topLevel->getContentsCount();
    }

    /**
     * @return float
     */
    public function getContentsTotalHeight()
    {
        return $this->topLevel->getContentsMaxHeight() + $this->completeLevelsTotalHeight;
    }
}