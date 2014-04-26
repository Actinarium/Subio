<?php
/**
 * @author Actine <actine@actinarium.com>
 * Date: 26.04.14
 * Time: 2:27
 */

namespace Actinarium\Subio\Exception;


class DataBlock {

    private $encodedData;
    private $binaryData;

    /**
     * @param mixed $binaryData
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
     * @return mixed
     */
    public function getBinaryData()
    {
        if ($this->binaryData === null) {

        }
        return $this->binaryData;
    }

    /**
     * @param mixed $encodedData
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
     * @return mixed
     */
    public function getEncodedData()
    {
        if ($this->encodedData === null) {

        }
        return $this->encodedData;
    }



} 
