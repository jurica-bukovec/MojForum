<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Kategorija;
use App\Tema;
use App\Komentar;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GlavniController extends Controller
{
   /* public function __construct()
    {
        $this->middleware('auth',['except' => ['kategorije','teme_kategorija']]);
    }*/

    public function kategorije()
    {
        $kategorije = Kategorija::with('teme')->orderBy('naziv_kategorije')->get();
        return view('welcome')->with('kategorije', $kategorije);
    }
    public function teme_kategorije($url)
    {
        $kategorija = Kategorija::where('url_naziv', $url)->firstOrFail();
        $teme = Tema::with('user','komentari','kategorija')->where('kategorija_id', $kategorija->id)->orderBy('created_at', 'desc')->paginate(20); //desc je novije
        return view('teme')->with('teme', $teme)->with('id', $kategorija->id);
    }
    public function zadnje_teme()
    {
        $teme = Tema::with('user','komentari','kategorija')->orderBy('created_at', 'desc')->paginate(20); //desc je novije
        return view('teme')->with('teme', $teme)->with('id', 0);
    }
    public function tema_komentari($slug)
    {   
        $tema= Tema::where('slug', $slug)->firstOrFail();
        $komentari= Komentar::with('user')->where('tema_id', $tema->id)->orderBy('created_at', 'asc')->paginate(10);
        $context = ['komentari'=>$komentari, 'tema'=>$tema];

        if(Auth::check()){
            if($tema->user_id == Auth::id()){
                $komentari_ids = [];
                foreach ($komentari as $komentar) {
                    array_push($komentari_ids, $komentar->id);
                }
                if (count($komentari_ids) > 0){
                Komentar::where('tema_id', $tema->id)->whereIn('id', $komentari_ids)->where('pogledano', false)->update(['pogledano' => true]);
            }
            }
        }
        return view('komentari')->with($context);

    }  
    public function kreiraj_temu(Request $request, $id)
    {
        Validator::make($request->all(), [
            'naslov' => 'required|max:190|unique:teme,naslov_teme',
        ], ['required' => 'Polje :attribute je obavezno.',
            'max' => 'Naslov je prevelik! Maksimalno 190 znakova.'])->validate();
        $nova_tema = new Tema;
        $nova_tema->naslov_teme = $request->naslov;
        $nova_tema->opis_teme = $request->opis;
        $nova_tema->kategorija_id = $id;
        $nova_tema->user_id = auth()->id();
        $nova_tema->save();
        return redirect()->back()->with('status', 'Tema je uspješno postavljena!');
    }

    public function ostavi_komentar(Request $request, $id)
    {
        Validator::make($request->all(), [
            'komentar' => 'required',
        ], ['required' => 'Polje komentara je obavezno.'])->validate();

        $novi_komentar = new Komentar;
        $novi_komentar->tekst_komentara = $request->komentar;
        $novi_komentar->tema_id = $id;
        $novi_komentar->user_id = auth()->id();
        $novi_komentar->save();
        $tema = Tema::with('komentari')->findOrFail($id);
        $url = route('komentari_na_temu', ['slug' => $tema->slug]);
        $count = $tema->komentari()->paginate(15)->lastPage();
        return redirect($url.'?page='.$count)->with('status', 'Komentar uspješno objavljen');
    }
    public function teme_korisnika($slug)
    {   
        $user = User::where('slug', $slug)->firstOrFail();
        $teme = Tema::with('komentari')->where('user_id', $user->id)->orderBy('created_at', 'desc');
        $teme_count = $teme->count();
        $teme = $teme->paginate(15);
        $komentari_count = Komentar::where('user_id', $user->id)->count();

        $context = [
            'teme' => $teme,
            'teme_count' => $teme_count,
            'komentari_count' => $komentari_count,
            'user' => $user,
        ];
        return view('teme_korisnika')->with($context);
    }

    public function komentari_korisnika($slug)
    {
        $user = User::where('slug', $slug)->firstOrFail();
        $komentari = Komentar::with('tema.kategorija')->where('user_id', $user->id)->orderBy('created_at', 'desc');
        $komentari_count = $komentari->count();
        $komentari = $komentari->paginate(15);
        $teme_count = Tema::where('user_id', $user->id)->count(); 
        $context = [
            'komentari' => $komentari,
            'komentari_count' => $komentari_count,
            'teme_count' => $teme_count,
            'user' => $user,
        ];
        return view('komentari_korisnika')->with($context);
    }
    public function pretrazi_teme(Request $request)
    {
        if($request->has('q')){
        $query = $request->q;
        }
        else {
            $query = $request->get('query');
        }
        $teme = Tema::search($query)->paginate(20);//appends($request->only('q'));
        $teme->load('komentari', 'user');
        $data = [
            'teme' => $teme,
            'query'   => $query
        ];
        return view('teme_pretrazivanje')->with($data);
    }

    public function izbrisi_temu(Request $request)
    {   
        $tema = Tema::findOrFail($request->id_teme);
        if(Auth::user()->id == $tema->user_id || Auth::user()->is_admin == true){
        $tema->delete();
        return redirect()->back()->with('status', 'Tema je uspješno izbrisana!');
        }
        else{
            return redirect()->back()->with('error', 'Nemate ovlasti za brisanje ove teme!');
        }
    }
    
    public function izbrisi_komentar(Request $request)
    {   
        $komentar = Komentar::findOrFail($request->id_komentara);
        if(Auth::user()->id == $komentar->user_id || Auth::user()->is_admin == true){
        $komentar->delete();
        return redirect()->back()->with('status', 'Komentar je uspješno izbrisan!');
        }
        else{
            return redirect()->back()->with('error', 'Nemate ovlasti za brisanje ovg komentara!');
        }
    }
}
