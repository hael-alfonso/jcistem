@if($person)
    <span class="person-chip">
        <span class="person-chip-avatar" aria-hidden="true">
            @if(!empty($person->profile['photo_path']))
                <img src="{{ route('projects.people.photo', [$project, $person]) }}" alt="">
            @else
                {{ strtoupper(mb_substr($person->name, 0, 1)) }}
            @endif
        </span>
        <span class="person-chip-name">{{ $person->name }}</span>
    </span>
@else
    <span class="person-chip-empty">Not assigned</span>
@endif
