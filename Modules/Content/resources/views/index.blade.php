<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('blog.frontend_title') }} | TAGS.BLOG</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">

    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800">{{ __('blog.frontend_title') }}</h1>
            
            <div class="space-x-2">
                <a href="?locale=de" class="text-sm font-bold {{ app()->getLocale() == 'de' ? 'text-blue-600 underline' : 'text-gray-500' }}">DE</a>
                <a href="?locale=en" class="text-sm font-bold {{ app()->getLocale() == 'en' ? 'text-blue-600 underline' : 'text-gray-500' }}">EN</a>
                <a href="?locale=hu" class="text-sm font-bold {{ app()->getLocale() == 'hu' ? 'text-blue-600 underline' : 'text-gray-500' }}">HU</a>
                <a href="?locale=sk" class="text-sm font-bold {{ app()->getLocale() == 'sk' ? 'text-blue-600 underline' : 'text-gray-500' }}">SK</a>
            </div>
        </div>

        @foreach($posts as $post)
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold mb-2">
                    @if($post->slug)
                        <a href="{{ route('blog.show', $post->slug) }}" class="text-blue-600 hover:underline">
                            {{ $post->title }}
                        </a>
                    @else
                        <span class="text-gray-800">{{ $post->title }}</span>
                    @endif
                </h2>

                <div class="text-sm text-gray-500 mb-4">
                    {{ $post->published_at ? $post->published_at->format('d.m.Y') : '' }} 
                </div>

                <div class="prose max-w-none text-gray-700">
                    {!! Str::limit(strip_tags($post->content), 200) !!}
                </div>
            </div>
        @endforeach
        
        <div class="mt-10 text-center text-gray-500 text-sm">
            &copy; {{ date('Y') }} TAGS.WORLD | {{ __('blog.copyright') }}
        </div>
    </div>

</body>
</html>