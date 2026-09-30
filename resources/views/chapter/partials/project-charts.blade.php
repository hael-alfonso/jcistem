<section class="dashboard-grid chart-grid" aria-label="Project charts">
    <div class="panel chart-panel">
        <div class="panel-heading">
            <div><h2>Projects by status</h2><p>Current status of projects visible to you</p></div>
            <span class="chart-count">{{ $charts['total'] }} total</span>
        </div>
        @if($charts['total'])
            <div class="chart-list">
                @php $statusMax = max(1, (int) $charts['statuses']->max()); @endphp
                @foreach($charts['statuses'] as $label => $count)
                    <div class="chart-row">
                        <div class="chart-row-label"><span>{{ $label }}</span><strong>{{ $count }}</strong></div>
                        <div class="chart-track"><span class="chart-fill blue" style="width: {{ 100 * $count / $statusMax }}%" aria-hidden="true"></span></div>
                    </div>
                @endforeach
            </div>
            <p class="chart-note">Each project appears once, under its current status. Bars are scaled to the largest count.</p>
        @else
            <div class="empty-state compact"><h3>No project data yet</h3><p>Project status counts will appear when projects are added.</p></div>
        @endif
    </div>
    <div class="panel chart-panel">
        <div class="panel-heading">
            <div><h2>Areas of opportunity</h2><p>Visible projects by JCI focus area</p></div>
        </div>
        @if($charts['total'])
            <div class="chart-list">
                @php $areaMax = max(1, (int) $charts['areas']->max()); @endphp
                @foreach($charts['areas'] as $label => $count)
                    <div class="chart-row">
                        <div class="chart-row-label"><span>{{ $label }}</span><strong>{{ $count }}</strong></div>
                        <div class="chart-track"><span class="chart-fill teal" style="width: {{ 100 * $count / $areaMax }}%" aria-hidden="true"></span></div>
                    </div>
                @endforeach
            </div>
            <p class="chart-note">Counts reflect the area saved on each visible project.</p>
        @else
            <div class="empty-state compact"><h3>No area data yet</h3><p>Focus areas will appear when projects are added.</p></div>
        @endif
    </div>
</section>
