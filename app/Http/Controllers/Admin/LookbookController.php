<?php

namespace App\Http\Controllers\Admin;

use App\DataGrids\LookbookDataGrid;
use App\Http\Controllers\Controller;
use App\Models\LookbookItem;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Repositories\ProductRepository;

class LookbookController extends Controller
{
    use ValidatesRequests;

    public function __construct(protected ProductRepository $productRepository) {}

    /**
     * Listing (DataGrid).
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(LookbookDataGrid::class)->toJson();
        }

        return view('lookbook::admin.index');
    }

    /**
     * Create form.
     */
    public function create()
    {
        return view('lookbook::admin.create');
    }

    /**
     * Persist a new look.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'type'            => 'required|in:image,reel',
            'image'           => 'required|image|mimes:jpeg,jpg,png,webp|max:4096',
            'video'           => 'nullable|file|mimetypes:video/mp4,video/webm,video/quicktime|max:51200',
            'video_url'       => 'nullable|url',
            'permalink'       => 'nullable|url',
            'title'           => 'nullable|string|max:255',
            'collection_name' => 'nullable|string|max:255',
            'caption'         => 'nullable|string',
            'display_order'   => 'nullable|integer|min:0',
        ]);

        $data = $this->prepareData($request);

        LookbookItem::create($data);

        session()->flash('success', trans('lookbook::app.admin.create-success'));

        return redirect()->route('admin.lookbook.index');
    }

    /**
     * Edit form.
     */
    public function edit(int $id)
    {
        $look = LookbookItem::findOrFail($id);

        $taggedProducts = $this->taggedProductsPayload($look->product_ids ?? []);

        return view('lookbook::admin.edit', compact('look', 'taggedProducts'));
    }

    /**
     * Update an existing look.
     */
    public function update(Request $request, int $id)
    {
        $look = LookbookItem::findOrFail($id);

        $this->validate($request, [
            'type'            => 'required|in:image,reel',
            'image'           => 'nullable|image|mimes:jpeg,jpg,png,webp|max:4096',
            'video'           => 'nullable|file|mimetypes:video/mp4,video/webm,video/quicktime|max:51200',
            'video_url'       => 'nullable|url',
            'permalink'       => 'nullable|url',
            'title'           => 'nullable|string|max:255',
            'collection_name' => 'nullable|string|max:255',
            'caption'         => 'nullable|string',
            'display_order'   => 'nullable|integer|min:0',
        ]);

        $data = $this->prepareData($request, $look);

        $look->update($data);

        session()->flash('success', trans('lookbook::app.admin.update-success'));

        return redirect()->route('admin.lookbook.index');
    }

    /**
     * Delete a look.
     */
    public function destroy(int $id)
    {
        $look = LookbookItem::findOrFail($id);

        if ($look->image) {
            Storage::disk('public')->delete($look->image);
        }

        if ($look->video) {
            Storage::disk('public')->delete($look->video);
        }

        $look->delete();

        return response()->json([
            'message' => trans('lookbook::app.admin.delete-success'),
        ]);
    }

    /**
     * Mass delete.
     */
    public function massDestroy(Request $request)
    {
        $items = LookbookItem::whereIn('id', (array) $request->input('indices', []))->get();

        foreach ($items as $item) {
            if ($item->image) {
                Storage::disk('public')->delete($item->image);
            }

            if ($item->video) {
                Storage::disk('public')->delete($item->video);
            }

            $item->delete();
        }

        return response()->json([
            'message' => trans('lookbook::app.admin.mass-delete-success'),
        ]);
    }

    /**
     * Mass status update.
     */
    public function massUpdate(Request $request)
    {
        LookbookItem::whereIn('id', (array) $request->input('indices', []))
            ->update(['status' => (int) $request->input('value')]);

        return response()->json([
            'message' => trans('lookbook::app.admin.mass-update-success'),
        ]);
    }

    /**
     * Product autocomplete for tagging.
     */
    public function searchProducts(Request $request)
    {
        $query = trim((string) $request->input('query'));

        if ($query === '') {
            return response()->json(['data' => []]);
        }

        $products = $this->productRepository
            ->scopeQuery(fn ($q) => $q->whereHas('product_flats', fn ($f) => $f->where('name', 'like', "%{$query}%"))->limit(10))
            ->all();

        return response()->json([
            'data' => $products->map(fn ($product) => $this->productTile($product))->values(),
        ]);
    }

    /**
     * Normalise request into model attributes (handles file upload).
     */
    protected function prepareData(Request $request, ?LookbookItem $look = null): array
    {
        $productIds = collect($request->input('product_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $data = [
            'title'           => $request->input('title'),
            'type'            => $request->input('type', 'image'),
            'video_url'       => $request->input('video_url'),
            'permalink'       => $request->input('permalink'),
            'collection_name' => $request->input('collection_name'),
            'caption'         => $request->input('caption'),
            'product_ids'     => $productIds,
            'display_order'   => (int) $request->input('display_order', 0),
            'is_featured'     => $request->boolean('is_featured'),
            'status'          => $request->boolean('status', true),
        ];

        if ($request->hasFile('image')) {
            if ($look && $look->image) {
                Storage::disk('public')->delete($look->image);
            }

            $data['image'] = $request->file('image')->store('lookbook', 'public');
        }

        if ($request->hasFile('video')) {
            if ($look && $look->video) {
                Storage::disk('public')->delete($look->video);
            }

            $data['video'] = $request->file('video')->store('lookbook/videos', 'public');
        }

        return $data;
    }

    /**
     * Hydrate already-tagged products for the edit screen.
     */
    protected function taggedProductsPayload(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $products = $this->productRepository->findWhereIn('id', $ids)->keyBy('id');

        return collect($ids)
            ->map(fn ($id) => $products->get($id))
            ->filter()
            ->map(fn ($product) => $this->productTile($product))
            ->values()
            ->all();
    }

    /**
     * Compact product representation used in the tagging UI.
     */
    protected function productTile($product): array
    {
        return [
            'id'    => $product->id,
            'name'  => $product->name,
            'sku'   => $product->sku,
            'image' => product_image()->getProductBaseImage($product)['small_image_url'] ?? null,
        ];
    }
}
