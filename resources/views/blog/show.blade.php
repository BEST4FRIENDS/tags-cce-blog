<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- WICHTIG: CSRF Token für die API-Tests --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>[Preview] {{ $post->meta_title ?: $post->title }}</title>
    
    {{-- Scripts & Styles --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    {{-- Alpine.js für die Interaktion --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        .heart-pulse { animation: pulse 0.3s ease-in-out; }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.2); } 100% { transform: scale(1); } }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased" 
      x-data="blogInteraction('{{ $post->id }}', {{ $post->likes ?? 0 }}, {{ $post->rating ?? 0 }}, {{ $post->rating_count ?? 0 }})">

    {{-- HEADER --}}
    <nav class="border-b border-gray-200 py-4 mb-8 sticky top-0 bg-white/90 backdrop-blur z-50 transition-all">
        <div class="max-w-4xl mx-auto px-4 flex justify-between items-center">
            <a href="{{ route('blog.index') }}" class="text-slate-500 hover:text-emerald-600 flex items-center gap-2 font-medium transition group">
                <i data-lucide="arrow-left" class="w-4 h-4 group-hover:-translate-x-1 transition-transform"></i>
                Zurück zur Übersicht
            </a>
            
            <div class="flex items-center gap-4">
                {{-- Sprach-Simulation --}}
                <div class="flex text-xs font-bold gap-2 text-slate-400 border-r border-gray-200 pr-4">
                    @foreach(['de', 'en', 'hu', 'sk'] as $lang)
                        <a href="?locale={{ $lang }}" class="{{ app()->getLocale() == $lang ? 'text-emerald-600 underline' : 'hover:text-slate-600' }}">
                            {{ strtoupper($lang) }}
                        </a>
                    @endforeach
                </div>
                <span class="text-xs font-mono text-emerald-600 bg-emerald-50 px-2 py-1 rounded border border-emerald-100 shadow-sm">
                    ● CCE PREVIEW MODE
                </span>
            </div>
        </div>
    </nav>

    <article class="max-w-4xl mx-auto px-4 pb-20">
        
        {{-- META INFO --}}
        <header class="mb-10 text-center">
            <div class="flex justify-center gap-2 mb-4">
                <span class="px-3 py-1 bg-white border border-slate-200 text-slate-600 text-xs font-bold uppercase rounded-full tracking-wide shadow-sm">
                    {{ $post->category->name ?? 'Blog' }}
                </span>
                
                @if($post->status !== 'published')
                     <span class="px-3 py-1 bg-amber-100 text-amber-700 text-xs font-bold uppercase rounded-full tracking-wide flex items-center gap-1">
                        <i data-lucide="lock" class="w-3 h-3"></i> {{ $post->status }}
                    </span>
                @endif
            </div>

            <h1 class="text-3xl md:text-5xl font-extrabold text-slate-900 mb-6 leading-tight">
                {{ $post->getTranslation('title', app()->getLocale()) ?: $post->title }}
            </h1>
            
            <div class="text-slate-500 text-sm flex justify-center items-center gap-6">
                <span class="flex items-center gap-1"><i data-lucide="calendar" class="w-4 h-4"></i> {{ $post->created_at->format('d.m.Y') }}</span>
                <span class="flex items-center gap-1"><i data-lucide="eye" class="w-4 h-4"></i> {{ $post->views }} Views</span>
                
                {{-- Lesezeit Berechnung (Sicher) --}}
                @php 
                    $plainText = \Modules\Content\Models\Post::extractTiptapText($post->getTranslation('content', app()->getLocale()));
                @endphp
                <span class="flex items-center gap-1"><i data-lucide="clock" class="w-4 h-4"></i> ~{{ ceil(str_word_count($plainText) / 200) }} Min.</span>
            </div>
        </header>

        {{-- BILD --}}
        @if($post->getFirstMediaUrl('posts'))
            <figure class="mb-12 rounded-2xl overflow-hidden shadow-xl ring-1 ring-gray-900/5 aspect-video relative group">
                <img src="{{ $post->getFirstMediaUrl('posts') }}" alt="{{ $post->title }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-700">
            </figure>
        @endif

        {{-- CONTENT (Sicher gegen Abstürze) --}}
        <div class="prose prose-lg prose-slate mx-auto prose-a:text-emerald-600 hover:prose-a:text-emerald-500 prose-img:rounded-xl prose-headings:font-bold">
            @php
                $content = $post->getTranslation('content', app()->getLocale()) ?: $post->content;
            @endphp
            
            @if(is_array($content) || is_object($content))
                <div class="p-4 bg-blue-50 text-blue-800 rounded-lg text-sm mb-4 border border-blue-100">
                    <strong>ℹ️ Simulator Hinweis:</strong> Der Inhalt liegt als Rohdaten (JSON) vor.<br>
                    Hier ist die Text-Vorschau:
                </div>
                <p>{{ \Modules\Content\Models\Post::extractTiptapText($content) }}</p>
            @else
                {!! $content !!}
            @endif
        </div>

        {{-- HASHTAGS --}}
        @if($post->tags->count() > 0)
            <div class="mt-12 pt-8 border-t border-gray-200 text-center">
                <div class="flex flex-wrap gap-2 justify-center">
                    @foreach($post->tags as $tag)
                        <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}" 
                           class="px-4 py-2 bg-white hover:bg-emerald-50 border border-gray-200 hover:border-emerald-200 rounded-full text-sm text-gray-600 hover:text-emerald-600 transition shadow-sm">
                            #{{ $tag->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- INTERAKTIONS-SIMULATOR (Die Test-Zentrale) --}}
        <div class="mt-16 bg-white rounded-2xl p-8 border border-slate-200 shadow-sm">
            <h3 class="text-center text-xs font-bold text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-100 pb-2">Simulator: API Tests</h3>
            
            <div class="flex flex-col md:flex-row justify-center items-center gap-8">
                
                {{-- Like Button (Funktionierend!) --}}
                <button @click="toggleLike()" :disabled="liked" class="flex items-center gap-3 px-6 py-3 bg-slate-50 rounded-xl border border-slate-200 hover:border-emerald-400 hover:bg-white transition active:scale-95 group">
                    <i data-lucide="heart" :class="liked ? 'fill-red-500 text-red-500 heart-pulse' : 'text-slate-400 group-hover:text-red-500'" class="w-5 h-5 transition-colors"></i>
                    <div class="text-left">
                        <span class="block font-bold text-slate-900 leading-none" x-text="count"></span>
                        <span class="text-[10px] uppercase font-bold text-slate-400">{{ __('blog.likes') }}</span>
                    </div>
                </button>

                {{-- Rating (Funktionierend!) --}}
                <div class="flex flex-col items-center">
                    <div class="flex gap-1 mb-1">
                        <template x-for="star in 5">
                            <button @click="rate(star)" class="p-1 hover:scale-110 transition focus:outline-none">
                                <i data-lucide="star" class="w-6 h-6" :class="star <= (userRating || Math.round(currentRating)) ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'"></i>
                            </button>
                        </template>
                    </div>
                    <span class="text-xs text-gray-400">{{ __('blog.rate_this') }}</span>
                </div>

                {{-- Share Dummy --}}
                <div class="flex gap-2 opacity-60 hover:opacity-100 transition">
                    <button class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-500 hover:bg-[#25D366] hover:text-white transition"><i data-lucide="message-circle" class="w-5 h-5"></i></button>
                </div>
            </div>
        </div>

    </article>

    {{-- LOGIK: Verbindet den Simulator mit der echten API --}}
    <script>
        lucide.createIcons();

        function blogInteraction(id, likes, rating, ratingCount) {
            return {
                postId: id,
                count: likes,
                currentRating: Number(rating),
                ratingCount: Number(ratingCount),
                liked: false,
                userRating: 0,

                async toggleLike() {
                    if(this.liked) return;
                    this.liked = true; this.count++;
                    // Feuert gegen die echte API -> Testet ob die Route existiert
                    try {
                        await fetch(`/api/v1/posts/${this.postId}/like`, { 
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                        });
                    } catch(e) { this.liked = false; this.count--; alert('API Fehler!'); }
                },

                async rate(stars) {
                    if(!confirm('Simuliere Bewertung: ' + stars + ' Sterne?')) return;
                    this.userRating = stars;
                    let res = await fetch(`/api/v1/posts/${this.postId}/rate`, { 
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') 
                        },
                        body: JSON.stringify({ rating: stars })
                    });
                    let data = await res.json();
                    if(data.status === 'success') {
                        this.currentRating = data.new_rating;
                        this.ratingCount++;
                    }
                }
            }
        }
        
        // Zählt den View (Testet den Session-Schutz)
        fetch(`/api/v1/posts/{{ $post->id }}/view`, { 
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
        });
    </script>
</body>
</html>