<?php
/**
 * @author Actine <actine@actinarium.com>
 * Date: 26.04.14
 * Time: 2:27
 */

namespace Actinarium\Subio;


class DataBlock
{

    /** @var  string */
    private $name;
    /** @var  string */
    private $encodedData;
    /** @var  string */
    private $binaryData;

    /**
     * @param string $binaryData
     *
     * @return DataBlock self-reference
     */
    public function setBinaryData($binaryData)
    {
        $this->encodedData = null;
        $this->binaryData = $binaryData;
        return $this;
    }

    /**
     * @return string
     */
    public function getBinaryData()
    {
        if ($this->binaryData === null) {
        }
        return $this->binaryData;
    }

    /**
     * @param string $encodedData
     *
     * @return DataBlock self-reference
     */
    public function setEncodedData($encodedData)
    {
        $this->binaryData = null;
        $this->encodedData = $encodedData;
        return $this;
    }

    /**
     * @return string
     */
    public function getEncodedData()
    {
        if ($this->encodedData === null) {
        }
        return $this->encodedData;
    }

    /**
     * Append encoded data chunk to existing data
     *
     * @param string $encodedData
     *
     * @return $this
     */
    public function appendEncodedData($encodedData)
    {
        $this->binaryData = null;
        $this->encodedData .= $encodedData;
        return $this;
    }

    public function getEncodedDataFormatted()
    {
        return chunk_split($this->getBinaryData(), 80, "\r\n");
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return DataBlock self-reference
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}
