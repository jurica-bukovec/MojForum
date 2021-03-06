<p class="m-0">Registracija: @if($user->created_at->isToday())
        Danas
    @elseif($user->created_at->isYesterday())
        Jučer
    @else
        {{$user->created_at->day}}.
        @switch($user->created_at->month)
        @case(1)
        Siječanja
        @break
        @case(2)
        Veljače
        @break
        @case(3)
        Ožujka
        @break
        @case(4)
        Travnja
        @break
        @case(5)
        Svibnja
        @break
        @case(6)
        Lipnja
        @break
        @case(7)
        Srpnja
        @break
        @case(8)
        Kolovoza
        @break
        @case(9)
        Rujna
        @break
        @case(10)
        Listopada
        @break
        @case(11)
        Studenog
        @break
        @case(12)
        Prosinca
        @break
        @endswitch
        {{$user->created_at->format('Y.')}}
    @endif
</p>