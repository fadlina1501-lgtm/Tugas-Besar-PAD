<?php
namespace App\Http\Controllers;
use App\Models\AssetCategory;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class MasterDataController extends Controller
{
    public function index()
    {
        $categories = AssetCategory::withCount('assets')->orderBy('name')->get();
        $locations = Location::withCount('assets')->orderBy('building')->orderBy('floor')->orderBy('room')->get();
        return view('master-data.index', compact('categories', 'locations'));
    }
    public function storeCategory(Request $request)
    {
        $data = $request->validate(['name' => ['required','string','max:100','unique:asset_categories,name']]);
        AssetCategory::create($data); return back()->with('success', 'Kategori aset berhasil ditambahkan.');
    }
    public function destroyCategory(AssetCategory $category)
    {
        if ($category->assets()->exists()) return back()->with('error', 'Kategori sedang digunakan aset dan tidak dapat dihapus.');
        $category->delete(); return back()->with('success', 'Kategori berhasil dihapus.');
    }
    public function storeLocation(Request $request)
    {
        $data = $request->validate(['building' => ['required','string','max:100'], 'floor' => ['required','integer','min:0','max:99'], 'room' => ['required','string','max:40']]);
        if (Location::where('building',$data['building'])->where('floor',$data['floor'])->where('room',$data['room'])->exists()) return back()->withErrors(['room' => 'Lokasi tersebut sudah terdaftar.'])->withInput();
        Location::create($data); return back()->with('success', 'Lokasi berhasil ditambahkan.');
    }
    public function destroyLocation(Location $location)
    {
        if ($location->assets()->exists()) return back()->with('error', 'Lokasi sedang digunakan aset dan tidak dapat dihapus.');
        $location->delete(); return back()->with('success', 'Lokasi berhasil dihapus.');
    }
}
