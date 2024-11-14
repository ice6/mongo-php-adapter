<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace Alcaeus\MongoDbAdapter\Helper;

/**
 * @internal
 */
trait ReadPreference
{
    /**
     * @var \MongoDB\Driver\ReadPreference
     */
    protected $readPreference;

    /**
     * @param string $readPreference
     * @param array $tags
     * @return bool
     */
    abstract public function setReadPreference($readPreference, $tags = null);

    /**
     * @return array
     */
    public function getReadPreference()
    {
        if ($this->readPreference === null) {
            $this->readPreference = new \MongoDB\Driver\ReadPreference(\MongoDB\Driver\ReadPreference::PRIMARY);
        }

        $mode = $this->readPreference->getModeString();
        $readPreference = ['type' => $mode];
        if ($this->readPreference->getTagSets() !== null && $this->readPreference->getTagSets() !== []) {
            $readPreference['tagsets'] = $this->readPreference->getTagSets();
        }

        return $readPreference;
    }

    /**
     * @return bool
     */
    protected function getSlaveOkayFromReadPreference()
    {
        return $this->readPreference->getModeString() != \MongoClient::RP_PRIMARY;
    }

    /**
     * @param string $readPreference
     * @param array $tags
     * @return bool
     */
    protected function setReadPreferenceFromParameters($readPreference, $tags = null)
    {
        // @internal Passing an array for $readPreference is necessary to avoid conversion voodoo
        // It should not be used externally!
        if (is_array($readPreference)) {
            return $this->setReadPreferenceFromArray($readPreference);
        }

        switch ($readPreference) {
            case \MongoClient::RP_PRIMARY:
                $mode = \MongoDB\Driver\ReadPreference::PRIMARY;
                break;
            case \MongoClient::RP_PRIMARY_PREFERRED:
                $mode = \MongoDB\Driver\ReadPreference::PRIMARY_PREFERRED;
                break;
            case \MongoClient::RP_SECONDARY:
                $mode = \MongoDB\Driver\ReadPreference::SECONDARY;
                break;
            case \MongoClient::RP_SECONDARY_PREFERRED:
                $mode = \MongoDB\Driver\ReadPreference::SECONDARY_PREFERRED;
                break;
            case \MongoClient::RP_NEAREST:
                $mode = \MongoDB\Driver\ReadPreference::NEAREST;
                break;
            default:
                trigger_error("The value '$readPreference' is not valid as read preference type", E_USER_WARNING);
                return false;
        }

        if ($readPreference == \MongoClient::RP_PRIMARY && !empty($tags)) {
            trigger_error("You can't use read preference tags with a read preference of PRIMARY", E_USER_WARNING);
            return false;
        }

        $this->readPreference = new \MongoDB\Driver\ReadPreference($mode, $tags);

        return true;
    }

    /**
     * @param array $readPreferenceArray
     * @return bool
     */
    protected function setReadPreferenceFromArray($readPreferenceArray)
    {
        $readPreference = $readPreferenceArray['type'];
        $tags = isset($readPreferenceArray['tagsets']) ? $readPreferenceArray['tagsets'] : [];

        return $this->setReadPreferenceFromParameters($readPreference, $tags);
    }

    /**
     * @param bool $ok
     * @return bool
     */
    protected function setReadPreferenceFromSlaveOkay($ok = true)
    {
        $result = $this->getSlaveOkayFromReadPreference();
        $readPreference = new \MongoDB\Driver\ReadPreference(
            $ok ? \MongoDB\Driver\ReadPreference::SECONDARY_PREFERRED : \MongoDB\Driver\ReadPreference::PRIMARY,
            $ok ? $this->readPreference->getTagSets() : []
        );

        $this->readPreference = $readPreference;

        return $result;
    }
}