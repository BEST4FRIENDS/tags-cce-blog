<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Blog Simulator | {{ $platform }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50 text-slate-800 font-sans antialiased">

    <div class="bg-slate-900 text-white p-4 sticky top-0 z-50 shadow-md">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
            
            <div class="flex items-center gap-6">
                <a href="/blog" class="font-bold text-xl tracking-tight text-emerald-400 hover:text-emerald-300 flex items-center gap-2">
                    <i data-lucide="monitor-play" class="w-6 h-6"></i> CCE SIMULATOR
                </a>
                
                {{-- Anzeige welche Plattform simuliert wird --}}
                <div class="hidden md:block px-3 py-1 bg-slate-800 rounded border border-slate-700 text-xs text-slate-300">
                    Target: <strong class="text-white">{{ $platform }}</strong>
                </div>
            </div>

            {{-- NEU: Meilisearch Live-Test --}}
            <form action="{{ route('blog.index') }}" method="GET" class="w-full md:w-1/3 relative">
                <input type="hidden" name="platform" value="{{ $platform }}">
                <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Meilisearch Index testen..." 
                       class="w-full bg-slate-800 border border-slate-700 rounded-full py-1.5 pl-10 pr-4 text-sm text-white focus:outline-none focus:border-emerald-500 transition placeholder-slate-500">
                <i data-lucide="search" class="w-4 h-4 text-slate-500 absolute left-3.5 top-2.5"></i>
            </form>

            <div class="flex gap-1 text-xs font-bold">
                @foreach(['de', 'en', 'hu', 'sk'] as $lang)
                    <a href="?platform={{$platform}}&locale={{$lang}}&search={{request('search')}}" 
                       class="px-2 py-1 rounded uppercase transition {{ app()->getLocale() == $lang ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        {{ $lang }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-12">
        
        {{-- Aktive Filter anzeigen --}}
        @if(request('search') || request('tag') || request('category'))
            <div class="mb-8 flex items-center gap-2 text-sm text-slate-500 bg-white p-3 rounded-lg shadow-sm border border-gray-100 inline-flex">
                <span class="font-bold uppercase text-xs tracking-wide text-slate-400">Filter aktiv:</span>
                @if(request('search')) <span class="bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded border border-emerald-100">"{{ request('search') }}"</span> @endif
                @if(request('tag')) <span class="bg-slate-100 px-2 py-0.5 rounded">#{{ request('tag') }}</span> @endif
                @if(request('category')) <span class="bg-slate-100 px-2 py-0.5 rounded">Kat: {{ request('category') }}</span> @endif
                <a href="{{ route('blog.index') }}" class="ml-2 text-red-400 hover:text-red-600"><i data-lucide="x" class="w-4 h-4"></i></a>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($posts as $post)
                <article class="bg-white rounded-xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden border border-gray-100 flex flex-col h-full group">
                    
                    <div class="aspect-video bg-gray-100 relative overflow-hidden">
                        <a href="{{ route('blog.show', $post->slug) }}">
                            @if($post->getFirstMediaUrl('posts'))
                                <img src="{{ $post->getFirstMediaUrl('posts') }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-700">
                            @else
                                <div class="flex items-center justify-center h-full text-gray-300"><i data-lucide="image" class="w-10 h-10"></i></div>
                            @endif
                        </a>
                        <div class="absolute top-3 left-3">
                            <span class="bg-white/95 backdrop-blur px-2 py-1 text-[10px] font-black uppercase rounded text-slate-800 shadow-sm border border-gray-100">
                                {{ $post->category->name ?? 'News' }}
                            </span>
                        </div>
                    </div>

                    <div class="p-6 flex-1 flex flex-col">
                        <h2 class="text-lg font-bold mb-3 leading-snug text-slate-900 group-hover:text-emerald-600 transition">
                            <a href="{{ route('blog.show', $post->slug) }}">
                                {{ $post->getTranslation('title', app()->getLocale()) ?: $post->title }}
                            </a>
                        </h2>
                        
                        <div class="text-slate-500 text-sm line-clamp-3 mb-4 flex-1">
                            @php
                                // FIX: Sicherer Text-Auszug auch bei JSON-Inhalt
                                $raw = $post->getTranslation('content', app()->getLocale()) ?: $post->content;
                                $text = \Modules\Content\Models\Post::extractTiptapText($raw);
                            @endphp
                            {{ Str::limit($text, 120) }}
                        </div>

                        <div class="mt-auto pt-4 border-t border-gray-50 flex justify-between items-center text-xs text-slate-400 font-medium">
                            <span class="flex items-center gap-1"><i data-lucide="calendar" class="w-3 h-3"></i> {{ $post->created_at->format('d.m.y') }}</span>
                            <span class="flex items-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i> {{ $post->views }}</span>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-3 py-24 text-center">
                    <div class="inline-block p-4 bg-slate-50 rounded-full mb-4"><i data-lucide="search-x" class="w-10 h-10 text-slate-300"></i></div>
                    <h3 class="text-lg font-bold text-slate-700">Keine Ergebnisse gefunden</h3>
                    <p class="text-slate-500 text-sm mt-1">Versuche einen anderen Suchbegriff oder Filter.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-12">
            {{ $posts->appends(request()->all())->links() }}
        </div>
    </div>
    
    <script>lucide.createIcons();</script>
</body>
</html>