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
            $this->binaryData = self::decode($this->encodedData);
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
            $this->encodedData = self::encode($this->binaryData);
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

    /**
     * Decode AAS data block into binary string
     *
     * @param string $data Content from AAS data block (font or graphics), in pseudo-UUEncode format
     *
     * @return string binary string
     */
    public static function decode(&$data)
    {
        $pointer = 0;
        $lenLimit = strlen($data) - 4;
        $output = '';
        while ($pointer <= $lenLimit) {
            $i = (ord($data[$pointer++]) - 0x21) << 18
                | (ord($data[$pointer++]) - 0x21) << 12
                | (ord($data[$pointer++]) - 0x21) << 6
                | (ord($data[$pointer++]) - 0x21);
            $output .= chr($i >> 16)
                . chr($i >> 8)
                . chr($i);
        }
        if ($pointer == $lenLimit + 2) {
            // two hanging chars - one output byte
            $i = (ord($data[$pointer++]) - 0x21) << 6
                | (ord($data[$pointer++]) - 0x21);
            $output .= chr($i >> 4);
        } elseif ($pointer == $lenLimit + 1) {
            // three hanging chars - two output bytes
            $i = (ord($data[$pointer++]) - 0x21) << 12
                | (ord($data[$pointer++]) - 0x21) << 6
                | (ord($data[$pointer++]) - 0x21);
            $output .= chr($i >> 10)
                . chr($i >> 2);
        }
        return $output;
    }

    /**
     * Encode binary string into AAS data block (font, graphics)
     *
     * @param string $data binary string
     *
     * @return string Content for AAS data block
     */
    public static function encode(&$data)
    {
        $pointer = 0;
        $lenLimit = strlen($data) - 2;
        $output = '';
        while ($pointer <= $lenLimit) {
            $i = ord($data[$pointer++]) << 16
                | ord($data[$pointer++]) << 8
                | ord($data[$pointer++]);
            $output .= chr(($i >> 18 & 0x3F) + 0x21)
                . chr(($i >> 12 & 0x3F) + 0x21)
                . chr(($i >> 6 & 0x3F) + 0x21)
                . chr(($i & 0x3F) + 0x21);
        }
        if ($pointer == $lenLimit + 2) {
            // one hanging byte
            $i = ord($data[$pointer]) << 4;
            $output .= chr(($i >> 6 & 0x3F) + 0x21)
                . chr(($i & 0x3F) + 0x21);
        } elseif ($pointer == $lenLimit + 1) {
            // two hanging bytes
            $i = ord($data[$pointer++]) << 10
                | ord($data[$pointer]) << 2;
            $output .= chr(($i >> 12 & 0x3F) + 0x21)
                . chr(($i >> 6 & 0x3F) + 0x21)
                . chr(($i & 0x3F) + 0x21);
        }
        return $output;
    }
}
