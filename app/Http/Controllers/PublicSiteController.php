<?php

namespace App\Http\Controllers;

use App\Catalog\PropertyCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PublicSiteController extends Controller
{
    public function home(PropertyCatalog $catalog): View
    {
        return view('home', ['featured' => $catalog->all()->where('featured', true), 'zones' => $catalog->zones()]);
    }

    public function index(Request $request, PropertyCatalog $catalog): View|RedirectResponse
    {
        $validator = Validator::make($request->query(), [
            'zone' => ['nullable', 'string', 'max:100'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'min_area' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'max_area' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'sort' => ['nullable', 'in:recent,price_asc,price_desc'],
        ]);
        if ($validator->fails()) {
            return redirect()->route('properties.index')->withErrors($validator);
        }
        $filters = $validator->validated();
        $properties = $catalog->all()->filter(function ($property) use ($filters) {
            if (! empty($filters['zone']) && $property['zone'] !== $filters['zone']) {
                return false;
            }
            foreach (['min_price' => ['price', true], 'max_price' => ['price', false], 'min_area' => ['area', true], 'max_area' => ['area', false]] as $key => [$field, $minimum]) {
                if (isset($filters[$key]) && ($minimum ? $property[$field] < $filters[$key] : $property[$field] > $filters[$key])) {
                    return false;
                }
            }

            return true;
        });
        $properties = match ($filters['sort'] ?? 'recent') {
            'price_asc' => $properties->sortBy('price'),
            'price_desc' => $properties->sortByDesc('price'),
            default => $properties->sortByDesc('date'),
        };

        return view('properties.index', compact('properties', 'filters') + ['zones' => $catalog->zones()]);
    }

    public function show(string $slug, PropertyCatalog $catalog): View
    {
        $property = $catalog->find($slug);
        abort_unless($property, 404);

        return view('properties.show', compact('property') + ['related' => $catalog->all()->where('slug', '!=', $slug)->sortByDesc(fn ($item) => $item['zone'] === $property['zone'])->take(3)]);
    }

    public function contact(Request $request, PropertyCatalog $catalog): View
    {
        $slug = $request->query('terreno');
        $property = is_string($slug) ? $catalog->find($slug) : null;

        return view('contact', compact('property'));
    }
}
