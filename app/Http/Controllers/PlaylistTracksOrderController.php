<?php namespace App\Http\Controllers;

use App\Models\Playlist;
use Common\Core\BaseController;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PlaylistTracksOrderController extends BaseController {

    public function change(Playlist $playlist): Response {

        $this->authorize('update', $playlist);

        $this->validate(request(), [
            'ids'   => 'array|min:1',
            'ids.*' => 'integer'
        ]);

        $ids = request()->get('ids');
        $queryPart = '';
        $bindings = [];
        foreach ($ids as $position => $id) {
            $position++;
            $queryPart .= ' when track_id=? then ?';
            $bindings[] = $id;
            $bindings[] = $position;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $prefix = DB::getTablePrefix();

        DB::update(
            "update {$prefix}playlist_track set `position` = (case $queryPart end) where playlist_id = ? and track_id in ($placeholders)",
            [...$bindings, $playlist->id, ...$ids],
        );

        return $this->success();
    }
}
