<?php

declare(strict_types=1);

namespace App\Dto;

class BsuDto
{
    public ?int $id = null;
    public ?string $title = null;

    public array $dc = [];

    public array $values = [];

    public array $authors = [];

    public array $links = [];

    public array $children = [];

    public int $total = 0;

    public array $files = [];

    public ?int $locationId = null;

    public ?string $locationText = null;

    public function make(array $data): self
    {
        if (isset($data['id'])) {
            $this->id = $data['id'];
        }
        if (isset($data['title'])) {
            $this->title = $data['title'];
        }
        if (isset($data['dc'])) {
            $this->dc = $data['dc'];
        }
        if (isset($data['values'])) {
            $this->values = $data['values'];
        }
        if (isset($data['authors'])) {
            $this->authors = $data['authors'];
        }
        if (isset($data['links'])) {
            $this->links = $data['links'];
        }
        if (isset($data['children'])) {
            $this->children = $data['children'];
        }
        if (isset($data['total'])) {
            $this->total = $data['total'];
        }
        if (isset($data['files'])) {
            $this->files = $data['files'];
        }
        if (isset($data['locationId'])) {
            $this->locationId = $data['locationId'];
        }
        if (isset($data['locationText'])) {
            $this->locationText = $data['locationText'];
        }

        return $this;
    }
}
