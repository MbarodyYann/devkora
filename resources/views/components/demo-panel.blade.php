{{-- Démo en un clic (02, P2), seulement si APP_DEMO=true. Les trois comptes ont le même poids :
     des boutons secondaires, le bouton principal de la page reste celui du formulaire. --}}
@if (App\Support\Demo::enabled())
    <section aria-labelledby="demo-titre" data-demo-panel {{ $attributes->class('rounded-2xl border border-line bg-bg-alt p-5 sm:rounded-card') }}>
        <h2 id="demo-titre" class="font-serif text-xl font-medium">Explore DevKora sans compte</h2>
        <p class="mt-1 text-sm text-ink-muted">Entre dans la peau d'un membre de la communauté, en un clic.</p>
        <div class="mt-4 grid gap-2">
            @foreach (App\Support\Demo::PERSONAS as $key => $persona)
                <form method="post" action="{{ route('demo.login', $key) }}">
                    @csrf
                    <button type="submit" class="btn-outline flex min-h-12 w-full flex-col items-start justify-center rounded-btn px-4 py-2 text-left">
                        <span class="font-semibold">Entrer comme {{ $persona['name'] }}</span>
                        <span class="text-sm text-ink-muted">{{ Str::ucfirst($persona['role']) }}</span>
                    </button>
                </form>
            @endforeach
        </div>
    </section>
@endif
