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
        return view('home', ['featured' => $catalog->featured(), 'zones' => $catalog->zones()]);
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
        $validator->after(function ($validator) use ($request) {
            foreach (['price', 'area'] as $field) {
                $min = $request->query('min_'.$field);
                $max = $request->query('max_'.$field);
                if (is_numeric($min) && is_numeric($max) && $min > $max) {
                    $validator->errors()->add('max_'.$field, 'El máximo debe ser mayor o igual al mínimo.');
                }
            }
        });
        if ($validator->fails()) {
            return redirect()->route('properties.index')->withErrors($validator);
        }
        $filters = $validator->validated();
        $request->session()->put('catalog_filters', array_filter($filters, fn ($value) => $value !== null && $value !== ''));
        $properties = $catalog->filtered($filters);

        return view('properties.index', compact('properties', 'filters') + ['zones' => $catalog->zones()]);
    }

    public function show(string $slug, PropertyCatalog $catalog): View
    {
        $property = $catalog->find($slug);
        abort_unless($property, 404);

        return view('properties.show', compact('property') + ['related' => $catalog->related($property)]);
    }

    public function contact(Request $request, PropertyCatalog $catalog): View
    {
        $slug = $request->query('terreno');
        $property = is_string($slug) ? $catalog->find($slug) : null;

        return view('contact', compact('property'));
    }
}
