<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- SEO & SOCIAL CARDS --}}
    <title>{{ $post->meta_title ?? $post->title }} | TAGS.BLOG</title>
    <meta name="description" content="{{ $post->meta_description ?? Str::limit(strip_tags(is_string($post->content) ? $post->content : ''), 150) }}">
    
    <meta property="og:title" content="{{ $post->meta_title ?? $post->title }}" />
    <meta property="og:description" content="{{ $post->meta_description ?? Str::limit(strip_tags(is_string($post->content) ? $post->content : ''), 150) }}" />
    <meta property="og:image" content="{{ $post->getFirstMediaUrl('default') }}" />
    <meta property="og:url" content="{{ request()->url() }}" />

    {{-- STYLING & SCRIPTS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Alpine.js für Interaktivität --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    {{-- Icons --}}
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        /* Typografie für den Blog-Content */
        .prose h2 { font-size: 1.5rem; font-weight: 800; margin-top: 2em; margin-bottom: 0.5em; color: #111827; }
        .prose h3 { font-size: 1.25rem; font-weight: 700; margin-top: 1.5em; margin-bottom: 0.5em; color: #374151; }
        .prose p { margin-bottom: 1.25em; line-height: 1.75; color: #4b5563; }
        .prose ul { list-style-type: disc; padding-left: 1.5em; margin-bottom: 1.25em; }
        .prose a { color: #2563eb; text-decoration: underline; text-underline-offset: 2px; }
        .prose blockquote { border-left: 4px solid #e5e7eb; padding-left: 1em; font-style: italic; color: #6b7280; }
        .prose img { border-radius: 0.75rem; margin-top: 2em; margin-bottom: 2em; }
        
        /* Animationen */
        .heart-pulse { animation: pulse 0.3s ease-in-out; }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.2); } 100% { transform: scale(1); } }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-800 antialiased" x-data="blogInteraction('{{ $post->id }}', {{ $post->likes ?? 0 }}, {{ $post->rating ?? 0 }}, {{ $post->rating_count ?? 0 }})">

    {{-- NAVIGATION --}}
    <nav class="bg-white shadow-sm sticky top-0 z-50 transition-all duration-300">
        <div class="max-w-5xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="{{ route('blog.index') }}" class="font-bold text-2xl tracking-tighter text-gray-900 hover:text-blue-600 transition">
                TAGS.<span class="text-blue-600">BLOG</span>
            </a>
            {{-- Dynamische Sprachwahl --}}
            <div class="space-x-3 text-sm font-bold">
                @foreach(['de', 'en', 'hu', 'sk'] as $lang)
                    <a href="?locale={{ $lang }}" class="{{ app()->getLocale() == $lang ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-gray-400 hover:text-gray-600' }}">
                        {{ strtoupper($lang) }}
                    </a>
                @endforeach
            </div>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 py-10 grid grid-cols-1 lg:grid-cols-12 gap-10">
        
        {{-- SIDEBAR (Inhaltsverzeichnis) - sticky --}}
        <aside class="hidden lg:block lg:col-span-3">
            <div class="sticky top-28">
                <h3 class="uppercase text-xs font-extrabold text-gray-400 mb-4 tracking-wider">{{ __('blog.table_of_contents') }}</h3>
                <nav id="toc-container" class="space-y-3 text-sm text-gray-600 border-l-2 border-gray-100 pl-4">
                    {{-- Wird per JS gefüllt --}}
                </nav>
                <div class="mt-8 pt-6 border-t border-gray-100">
                    <a href="{{ route('blog.index') }}" class="text-blue-600 hover:underline text-sm font-semibold flex items-center gap-2">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> {{ __('blog.back_to_overview') }}
                    </a>
                </div>
            </div>
        </aside>

        {{-- HAUPTINHALT --}}
        <main class="lg:col-span-9 bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
            
            {{-- COVER BILD --}}
            @if($post->getFirstMediaUrl('default'))
                <div class="relative w-full h-64 md:h-96">
                    <img src="{{ $post->getFirstMediaUrl('default') }}" alt="{{ $post->title }}" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                    
                    {{-- Kategorie Badge --}}
                    @if($post->category)
                        <div class="absolute bottom-6 left-6 md:left-10">
                            <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}" 
                               class="bg-blue-600 text-white px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide hover:bg-blue-700 transition shadow-lg">
                                {{ $post->category->name }}
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            <div class="p-8 md:p-12">
                
                {{-- META HEADER --}}
                <div class="flex flex-wrap items-center gap-6 text-sm text-gray-500 mb-6">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        {{ $post->published_at ? $post->published_at->format('d.m.Y') : __('blog.draft') }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                        {{ $post->views }}
                    </span>
                    {{-- Lesezeit Berechnung (Fallback auf reinen Text, falls Content HTML ist) --}}
                    @php 
                        $plainText = is_string($post->content) ? strip_tags($post->content) : \Modules\Content\Models\Post::extractTiptapText($post->content);
                    @endphp
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                        {{ ceil(str_word_count($plainText) / 200) }} {{ __('blog.read_time_min') }}
                    </span>
                </div>

                {{-- TITEL --}}
                <h1 class="text-3xl md:text-5xl font-black text-gray-900 mb-8 leading-tight">
                    {{ $post->title }}
                </h1>

                {{-- CONTENT --}}
                <article id="post-content" class="prose prose-lg prose-blue max-w-none text-gray-700 mb-12">
                    @if(is_array($post->content) || is_object($post->content))
                        {{-- FALLBACK: Wenn Content noch JSON ist (Tiptap) --}}
                        <div class="bg-amber-50 border-l-4 border-amber-500 p-4 text-amber-700 text-sm">
                            <strong>System-Hinweis:</strong> Der Inhalt liegt als Rohdaten (JSON) vor. <br>
                            Bitte installieren Sie <code>ueberdosis/tiptap-php</code> für die HTML-Konvertierung oder nutzen Sie den Text-Extraktor.
                            <br><br>
                            <em>Vorschau (Text):</em> <br>
                            {{ \Modules\Content\Models\Post::extractTiptapText($post->content) }}
                        </div>
                    @else
                        {{-- STANDARD: HTML Ausgabe --}}
                        {!! $post->content !!}
                    @endif
                </article>

                {{-- HASHTAGS (Dynamisch verlinkt) --}}
                @if($post->tags->count() > 0)
                    <div class="flex flex-wrap gap-2 mb-10 pt-8 border-t border-gray-100">
                        @foreach($post->tags as $tag)
                            <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}" 
                               class="text-sm text-gray-500 bg-gray-50 border border-gray-200 px-3 py-1 rounded-full hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition">
                                #{{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- INTERACTION AREA (Buttons) --}}
                <div class="bg-slate-50 rounded-2xl p-6 md:p-8 flex flex-col md:flex-row items-center justify-between gap-8 border border-slate-200">
                    
                    {{-- 1. LIKE BUTTON --}}
                    <button @click="toggleLike()" :disabled="liked" 
                        class="group flex items-center gap-3 px-6 py-3 bg-white rounded-xl shadow-sm border border-gray-200 transition-all transform active:scale-95 hover:shadow-md w-full md:w-auto justify-center">
                        <i data-lucide="heart" 
                           :class="liked ? 'text-red-500 fill-red-500 heart-pulse' : 'text-gray-400 group-hover:text-red-500'" 
                           class="w-6 h-6 transition-colors"></i>
                        <div class="text-left leading-none">
                            <span class="block font-bold text-lg text-gray-900" x-text="count"></span>
                            <span class="text-xs text-gray-500 uppercase font-bold tracking-wide" x-text="liked ? '{{ __('blog.liked') }}' : '{{ __('blog.likes') }}'"></span>
                        </div>
                    </button>

                    {{-- 2. RATING STARS --}}
                    <div class="flex flex-col items-center">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">{{ __('blog.rate_this') }}</span>
                        <div class="flex gap-1">
                            <template x-for="star in 5">
                                <button @click="rate(star)" class="focus:outline-none transition transform hover:scale-110 p-1">
                                    <i data-lucide="star" class="w-8 h-8" 
                                       :class="star <= (userRating || Math.round(currentRating)) ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300 hover:text-yellow-300'"></i>
                                </button>
                            </template>
                        </div>
                        <p class="text-xs text-gray-400 mt-2 font-medium">
                            <span x-text="currentRating.toFixed(1)"></span> / 5 
                            (<span x-text="ratingCount"></span> {{ __('blog.votes') }})
                        </p>
                    </div>

                    {{-- 3. SOCIAL SHARING --}}
                    <div class="flex flex-col items-center md:items-end">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">{{ __('blog.share') }}</span>
                        <div class="flex gap-2">
                            @php 
                                $shareUrl = urlencode(request()->url()); 
                                $shareTitle = urlencode($post->title);
                            @endphp
                            
                            {{-- WhatsApp --}}
                            <a href="https://api.whatsapp.com/send?text={{ $shareTitle }}%20{{ $shareUrl }}" target="_blank" class="w-10 h-10 flex items-center justify-center bg-[#25D366] text-white rounded-full hover:opacity-90 transition shadow-sm">
                                <i data-lucide="message-circle" class="w-5 h-5"></i>
                            </a>
                            {{-- Facebook --}}
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" class="w-10 h-10 flex items-center justify-center bg-[#1877F2] text-white rounded-full hover:opacity-90 transition shadow-sm">
                                <i data-lucide="facebook" class="w-5 h-5"></i>
                            </a>
                            {{-- LinkedIn --}}
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" class="w-10 h-10 flex items-center justify-center bg-[#0077B5] text-white rounded-full hover:opacity-90 transition shadow-sm">
                                <i data-lucide="linkedin" class="w-5 h-5"></i>
                            </a>
                            {{-- Email --}}
                            <a href="mailto:?subject={{ $shareTitle }}&body={{ $shareUrl }}" class="w-10 h-10 flex items-center justify-center bg-gray-600 text-white rounded-full hover:opacity-90 transition shadow-sm">
                                <i data-lucide="mail" class="w-5 h-5"></i>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <footer class="text-center text-gray-400 text-sm py-12 border-t border-gray-100 mt-12 bg-white">
        &copy; {{ date('Y') }} TAGS.WORLD | {{ __('blog.copyright') }}
    </footer>

    {{-- INTERACTIVITY LOGIC --}}
    <script>
        lucide.createIcons(); // Icons initialisieren

        // 1. Alpine Component für API Calls
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
                    
                    // Optimistic UI Update (Sofortiges Feedback)
                    this.liked = true;
                    this.count++;

                    try {
                        let res = await fetch(`/api/v1/posts/${this.postId}/like`, { 
                            method: 'POST',
                            headers: { 
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') 
                            }
                        });
                        if(!res.ok) { throw new Error(); }
                    } catch(e) {
                        // Rollback bei Fehler
                        this.liked = false; 
                        this.count--;
                    }
                },

                async rate(stars) {
                    if(!confirm('{{ __('blog.confirm_rate') }} ' + stars + '*?')) return;
                    this.userRating = stars;

                    try {
                        let res = await fetch(`/api/v1/posts/${this.postId}/rate`, { 
                            method: 'POST',
                            headers: { 
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') 
                            },
                            body: JSON.stringify({ rating: stars })
                        });
                        let data = await res.json();
                        if(data.status === 'success') {
                            this.currentRating = data.new_rating;
                            this.ratingCount++;
                            alert('{{ __('blog.thank_you_rating') }}');
                        }
                    } catch(e) { console.error(e); }
                }
            }
        }

        // 2. Automatisches Inhaltsverzeichnis (TOC)
        document.addEventListener('DOMContentLoaded', () => {
            const content = document.getElementById('post-content');
            const toc = document.getElementById('toc-container');
            const headings = content.querySelectorAll('h2, h3');

            if(headings.length === 0) {
                document.querySelector('aside').style.display = 'none'; // Sidebar ausblenden wenn leer
                return;
            }

            headings.forEach((heading, index) => {
                const id = 'h-' + index;
                heading.id = id;
                
                const link = document.createElement('a');
                link.href = '#' + id;
                link.innerText = heading.innerText;
                // Styling je nach Level
                link.className = 'block hover:text-blue-600 transition truncate ' + 
                                 (heading.tagName === 'H3' ? 'pl-4 text-xs' : 'font-bold');
                
                const li = document.createElement('li');
                li.appendChild(link);
                toc.appendChild(li);
            });
        });

        // 3. View Counter (Silent)
        fetch(`/api/v1/posts/{{ $post->id }}/view`, { 
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
        });
    </script>
</body>
</html>