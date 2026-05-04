<?php

namespace Common\Channels;

use App\Models\Channel;
use Common\Core\BaseController;
use Illuminate\Support\Facades\DB;

class ChannelContentOrderController extends BaseController
{
    public function changeOrder(int $channelId)
    {
        $channel = Channel::findOrFail($channelId);

        $this->authorize('update', $channel);
        $this->blockOnDemoSite();

        $data = request()->validate([
            'ids' => 'array|min:1',
            'ids.*' => 'int',
            'modelType' => 'required|string',
        ]);

        $queryPart = '';
        $bindings = [];
        foreach ($data['ids'] as $order => $id) {
            $queryPart .= ' when channelable_id=? then ?';
            $bindings[] = $id;
            $bindings[] = $order;
        }

        $placeholders = implode(',', array_fill(0, count($data['ids']), '?'));
        $prefix = DB::getTablePrefix();

        DB::update(
            "update {$prefix}channelables set `order` = (case $queryPart end) where channel_id = ? and channelable_type = ? and channelable_id in ($placeholders)",
            [
                ...$bindings,
                $channel->id,
                $data['modelType'],
                ...$data['ids'],
            ],
        );

        // update timestamp to trigger cache invalidation
        $channel->touch();

        return $this->success();
    }
}
