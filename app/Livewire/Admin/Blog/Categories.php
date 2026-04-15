<?php

namespace App\Livewire\Admin\Blog;

use App\Models\BlogCategory;
use Illuminate\Support\Str;
use Livewire\Component;

class Categories extends Component
{
    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public string $color = '#1a56db';
    public ?string $editingId = null;
    public string $savedMessage = '';

    protected function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'slug'        => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string', 'max:280'],
            'color'       => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);
    }

    public function updatedName(): void
    {
        if (! $this->editingId && empty($this->slug)) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function startEdit(string $id): void
    {
        $c = BlogCategory::findOrFail($id);
        $this->editingId   = $c->id;
        $this->name        = $c->name;
        $this->slug        = $c->slug;
        $this->description = $c->description ?? '';
        $this->color       = $c->color ?? '#1a56db';
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $data = $this->validate();

        $exists = BlogCategory::where('slug', $data['slug'])
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->exists();
        if ($exists) {
            $this->addError('slug', 'A category with that slug already exists.');
            return;
        }

        if ($this->editingId) {
            BlogCategory::findOrFail($this->editingId)->update($data);
            $this->savedMessage = 'Category updated.';
        } else {
            BlogCategory::create($data);
            $this->savedMessage = 'Category created.';
        }

        $this->resetForm();
    }

    public function deleteCategory(string $id): void
    {
        $c = BlogCategory::findOrFail($id);
        // FK is nullOnDelete — posts keep existing, just lose category.
        $c->delete();
        $this->savedMessage = 'Category deleted. Posts in it are now uncategorised.';
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'slug', 'description', 'editingId']);
        $this->color = '#1a56db';
    }

    public function render()
    {
        return view('livewire.admin.blog.categories', [
            'categories' => BlogCategory::orderBy('name')->get(),
        ])->layout('layouts.admin');
    }
}
