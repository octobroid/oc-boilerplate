<?php namespace Editor\Classes;

/**
 * Contains information required for creating new documents in Editor.
 *
 * @package october\editor
 * @author Alexey Bobkov, Samuel Georges
 */
class NewDocumentDescription
{
    private $label;
    private $icon;
    private $metadata;
    private $documentData;

    public function __construct(string $label, array $metadata) {
        $this->label = $label;
        $this->metadata = $metadata;
    }

    public function setIcon(string $backgroundColor, string $iconClassName)
    {
        $this->icon = [
            'backgroundColor' => $backgroundColor,
            'cssClass' => $iconClassName
        ];

        return $this;
    }

    public function setInitialDocumentData($documentData) {
        $this->documentData = $documentData;
    }

    public function toArray()
    {
        $result = [
            'label' => $this->label,
            'metadata' => $this->metadata
        ];

        if ($this->icon) {
            $result['icon'] = $this->icon;
        }

        if ($this->documentData) {
            $result['document'] = $this->documentData;
        }

        return $result;
    }
}