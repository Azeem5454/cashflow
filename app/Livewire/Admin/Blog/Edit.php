<?php

namespace App\Livewire\Admin\Blog;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\UploadedAsset;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use WithFileUploads;

    public ?BlogPost $post = null;

    // form fields
    public string $title = '';
    public string $slug = '';
    public string $excerpt = '';
    public string $body_markdown = '';
    public ?string $category_id = null;
    public string $status = 'draft';
    public bool $is_featured = false;
    public ?string $published_at = null;
    public string $seo_title = '';
    public string $seo_description = '';
    public string $featured_image_alt = '';

    public $featuredImageUpload = null;    // Livewire upload
    public bool $removeFeaturedImage = false;

    // UX state
    public string $savedMessage = '';
    public bool $showPreview = false;
    public string $previewHtml = '';

    public function mount(?string $id = null): void
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);

        if ($id) {
            $this->post = BlogPost::findOrFail($id);
            $this->title              = $this->post->title ?? '';
            $this->slug               = $this->post->slug ?? '';
            $this->excerpt            = $this->post->excerpt ?? '';
            $this->body_markdown      = $this->post->body_markdown ?? '';
            $this->category_id        = $this->post->category_id;
            $this->status             = $this->post->status ?? 'draft';
            $this->is_featured        = (bool) $this->post->is_featured;
            $this->published_at       = $this->post->published_at?->format('Y-m-d\TH:i');
            $this->seo_title          = $this->post->seo_title ?? '';
            $this->seo_description    = $this->post->seo_description ?? '';
            $this->featured_image_alt = $this->post->featured_image_alt ?? '';
        }
    }

    public function updatedTitle(): void
    {
        // Auto-slug only while creating a new post and slug hasn't been manually touched.
        if (! $this->post && empty($this->slug)) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function refreshPreview(): void
    {
        $this->previewHtml = BlogPost::renderMarkdown($this->body_markdown);
    }

    public function togglePreview(): void
    {
        $this->showPreview = ! $this->showPreview;
        if ($this->showPreview) {
            $this->refreshPreview();
        }
    }

    public function removeExistingImage(): void
    {
        $this->removeFeaturedImage = true;
    }

    protected function rules(): array
    {
        return [
            'title'               => ['required', 'string', 'max:200'],
            'slug'                => ['required', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'excerpt'             => ['nullable', 'string', 'max:400'],
            'body_markdown'       => ['nullable', 'string', 'max:200000'],
            'category_id'         => ['nullable', 'uuid', 'exists:blog_categories,id'],
            'status'              => ['required', 'in:draft,published'],
            'is_featured'         => ['boolean'],
            'published_at'        => ['nullable', 'date'],
            'seo_title'           => ['nullable', 'string', 'max:160'],
            'seo_description'     => ['nullable', 'string', 'max:280'],
            'featured_image_alt'  => ['nullable', 'string', 'max:200'],
            'featuredImageUpload' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072'], // 3 MB
        ];
    }

    public function save(bool $keepEditing = true): void
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);

        $data = $this->validate();

        $isNew = ! $this->post;

        // Ensure slug uniqueness (migration has UNIQUE but give a graceful error).
        $slugExists = BlogPost::where('slug', $data['slug'])
            ->when($this->post, fn ($q) => $q->where('id', '!=', $this->post->id))
            ->exists();
        if ($slugExists) {
            $this->addError('slug', 'A post with that slug already exists. Pick another.');
            return;
        }

        if ($isNew) {
            $this->post = new BlogPost();
            $this->post->author_id = auth()->id();
        }

        $this->post->fill([
            'title'              => $data['title'],
            'slug'               => $data['slug'],
            'excerpt'            => $data['excerpt'] ?: null,
            'body_markdown'      => $data['body_markdown'] ?: null,
            'category_id'        => $data['category_id'] ?: null,
            'status'             => $data['status'],
            'is_featured'        => $data['is_featured'],
            'published_at'       => $data['published_at'] ?: null,
            'seo_title'          => $data['seo_title'] ?: null,
            'seo_description'    => $data['seo_description'] ?: null,
            'featured_image_alt' => $data['featured_image_alt'] ?: null,
        ]);

        // Enforce single-featured rule.
        if ($this->post->is_featured) {
            BlogPost::where('is_featured', true)
                ->when($this->post->id, fn ($q) => $q->where('id', '!=', $this->post->id))
                ->update(['is_featured' => false]);
        }

        // Handle featured image upload / removal.
        $this->post->save(); // save first so we have an ID for the asset key

        if ($this->removeFeaturedImage && $this->post->featured_image_key) {
            UploadedAsset::forgetKey($this->post->featured_image_key);
            $this->post->featured_image_key = null;
            $this->post->save();
        }

        if ($this->featuredImageUpload) {
            $bytes = file_get_contents($this->featuredImageUpload->getRealPath());
            $mime  = $this->featuredImageUpload->getMimeType() ?: 'image/png';
            $key   = 'blog-post-' . $this->post->id . '-featured';
            UploadedAsset::put($key, $bytes, $mime);
            // Old image (if any) — different key format — clean up.
            if ($this->post->featured_image_key && $this->post->featured_image_key !== $key) {
                UploadedAsset::forgetKey($this->post->featured_image_key);
            }
            $this->post->featured_image_key = $key;
            $this->post->save();
            $this->featuredImageUpload = null;
        }

        $this->removeFeaturedImage = false;

        $this->savedMessage = $isNew ? 'Post created.' : 'Post updated.';

        if (! $keepEditing) {
            $this->redirect(route('admin.blog.index'));
            return;
        }

        // If we just created the post, redirect to the edit URL so further saves hit the right row.
        if ($isNew) {
            $this->redirect(route('admin.blog.edit', ['id' => $this->post->id]));
        }
    }

    public function saveAndClose(): void
    {
        $this->save(keepEditing: false);
    }

    public function deletePost(): void
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);

        if ($this->post) {
            if ($this->post->featured_image_key) {
                UploadedAsset::forgetKey($this->post->featured_image_key);
            }
            $this->post->delete();
        }
        $this->redirect(route('admin.blog.index'));
    }

    public function render()
    {
        return view('livewire.admin.blog.edit', [
            'categories' => BlogCategory::orderBy('name')->get(),
        ])->layout('layouts.admin');
    }
}
