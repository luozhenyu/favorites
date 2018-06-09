<?php
/**
 * Ok, glad you are here
 * first we get a config instance, and set the settings
 * $config = HTMLPurifier_Config::createDefault();
 * $config->set('Core.Encoding', $this->config->get('purifier.encoding'));
 * $config->set('Cache.SerializerPath', $this->config->get('purifier.cachePath'));
 * if ( ! $this->config->get('purifier.finalize')) {
 *     $config->autoFinalize = false;
 * }
 * $config->loadArray($this->getConfig());
 *
 * You must NOT delete the default settings
 * anything in settings should be compacted with params that needed to instance HTMLPurifier_Config.
 *
 * @link http://htmlpurifier.org/live/configdoc/plain.html
 */

return [
    'encoding' => 'UTF-8',
    'finalize' => true,
    'cachePath' => storage_path('app/purifier'),
    'cacheFileMode' => 0755,
    'settings' => [
        'default' => [
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            'HTML.Allowed' => 'a[class|style],abbr[class|style],b[class|style],blockquote[class|style],br[class|style],code[class|style],col[span|width|class|style],colgroup[span|width|class|style],dd[class|style],del[class|style],div[class|style],dl[class|style],dt[class|style],em[class|style],h1[class|style],h2[class|style],h3[class|style],h4[class|style],h5[class|style],h6[class|style],hr[class|style],i[class|style],img[alt|src|height|width|class|style],ins[class|style],li[class|style],ol[start|type|class|style],p[class|style],q[class|style],span[class|style],strong[class|style],sub[class|style],sup[class|style],table[width|class|style],tbody[class|style],td[colspan|height|rowspan|width|class|style],tfoot[class|style],th[colspan|height|rowspan|width|class|style],thead[class|style],tr[class|style],ul[class|style]',
            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.RemoveEmpty' => true,
            'AutoFormat.RemoveSpansWithoutAttributes' => true,
        ],
    ],

];
