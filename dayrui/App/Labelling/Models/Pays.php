<?php namespace Phpcmf\Model\Labelling;

class Pays extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

        $name = '$'.$pix.$field['fieldname'];
        
        $html = '';
        $html.= PHP_EOL.'              商品编号&lt;span id=&quot;dr_sku_sn&quot;&gt; {'.$name.'_sn} &lt;/span&gt;&lt;br&gt;';
        $html.= PHP_EOL.'              {if '.$name.'_sku}';
        $html.= PHP_EOL.'              {loop '.$name.'_sku[\'group\'] $gid $gname}';
        $html.= PHP_EOL.'              {if $gname}';
        $html.= PHP_EOL.'              &lt;label class=&quot;fc-sku-group-html&quot;&gt;{$gname}&lt;/label&gt;';
        $html.= PHP_EOL.'              &lt;div class=&quot;fc-sku-select-price&quot;&gt;';
        $html.= PHP_EOL.'                  {php $i=0;}';
        $html.= PHP_EOL.'                  {loop '.$name.'_sku[\'name\'][$gid] $vid $vname}';
        $html.= PHP_EOL.'                  &lt;button type=&quot;button&quot; fvalue=&quot;{$gid}_{$vid}&quot; fname=&quot;{$vname}&quot; fsn=&quot;{'.$name.'_sku[\'value\'][$gid.\'_\'.$vid][\'sn\']}&quot; class=&quot;fc-sku-value btn {if $i==$sku_value[$gid]}red{/if} btn-default btn-xs&quot;&gt;{$vname}&lt;/button&gt;';
        $html.= PHP_EOL.'                  {php $i=1;}';
        $html.= PHP_EOL.'                  {/loop}';
        $html.= PHP_EOL.'              &lt;/div&gt;';
        $html.= PHP_EOL.'              {/if}';
        $html.= PHP_EOL.'              {/loop}';
        $html.= PHP_EOL.'              &lt;input type=&quot;hidden&quot; id=&quot;dr_sku_value&quot; value=&quot;&quot;&gt;';
        $html.= PHP_EOL.'              {loop '.$name.'_sku[\'value\'] $i $v}';
        $html.= PHP_EOL.'              &lt;input type=&quot;hidden&quot; id=&quot;dr_sku_sn_{$i}&quot; value=&quot;{$v.sn}&quot;&gt;';
        $html.= PHP_EOL.'              &lt;input type=&quot;hidden&quot; id=&quot;dr_sku_price_{$i}&quot; value=&quot;{number_format($v.price,2)}&quot;&gt;';
        $html.= PHP_EOL.'              &lt;input type=&quot;hidden&quot; id=&quot;dr_sku_quantity_{$i}&quot; value=&quot;{$v.quantity}&quot;&gt;';
        $html.= PHP_EOL.'              &lt;input type=&quot;hidden&quot; id=&quot;dr_sku_promotion_{$i}&quot; value=&quot;{$promotion[$i]}&quot;&gt;';
        $html.= PHP_EOL.'              {/loop}';
        $html.= PHP_EOL.'              {/if}';
        $html.= PHP_EOL.'              &lt;br&gt;';
        $html.= PHP_EOL.'              库存剩余&lt;span id=&quot;dr_sku_quantity&quot;&gt; {'.$name.'_quantity} &lt;/span&gt;件';


        $html.= PHP_EOL.'              &lt;script type=&quot;text/javascript&quot; src=&quot;{THEME_PATH}assets/js/sku.js&quot;&gt;&lt;/script&gt;';
        $html.= PHP_EOL.'              &lt;script type=&quot;text/javascript&quot;&gt;';

        $html.= PHP_EOL.'                  $(function () {';
        $html.= PHP_EOL.'                      select_sku_price();';
        $html.= PHP_EOL.'                      get_sku_price();';

        $html.= PHP_EOL.'                  })';
        $html.= PHP_EOL.'                  // 组合价格选择';
        $html.= PHP_EOL.'                  function select_sku_price() {';
        $html.= PHP_EOL.'                      $(\'.fc-sku-select-price .fc-sku-value\').click(function () {';
        $html.= PHP_EOL.'                          $(this).parent(\'.fc-sku-select-price\').find(\'.fc-sku-value\').removeClass(\'red\');';
        $html.= PHP_EOL.'                          $(this).addClass(\'red\');';
        $html.= PHP_EOL.'                          get_sku_price();';

        $html.= PHP_EOL.'                      });';
        $html.= PHP_EOL.'                  }';
        $html.= PHP_EOL.'                  function get_sku_price() {';
        $html.= PHP_EOL.'                      var oname = new Array();';
        $html.= PHP_EOL.'                      $(\'.fc-sku-select-price\').each(function () {';
        $html.= PHP_EOL.'                          oname.push($(this).find(\'.red\').attr(\'fvalue\'));';
        $html.= PHP_EOL.'                      });';
        $html.= PHP_EOL.'                      var k = oname.join(&quot;_&quot;);';
        $html.= PHP_EOL.'                      $(\'#dr_sku_value\').val(k);';
        $html.= PHP_EOL.'                      $(\'#dr_sku_price\').html($(\'#dr_sku_price_\'+k).val());';
        $html.= PHP_EOL.'                      $(\'#dr_sku_quantity\').html($(\'#dr_sku_quantity_\'+k).val());';
        $html.= PHP_EOL.'                      $(\'#dr_sku_sn\').html($(\'#dr_sku_sn_\'+k).val());';
        $html.= PHP_EOL.'                      {if $promotion}';
        $html.= PHP_EOL.'                      $(\'#dr_promotion_price\').html($(\'#dr_sku_promotion_\'+k).val());';
        $html.= PHP_EOL.'                      {/if}';

        $html.= PHP_EOL.'                  }';
        $html.= PHP_EOL.'              &lt;/script&gt;';

        return dr_code2html($html);
    }
}