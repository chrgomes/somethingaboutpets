<?php
declare(strict_types=1);

/**
 * The Something About Pets cat + dog line mark as an inline, animatable SVG.
 * Redrawn as strokes from the logo so it can "draw itself" (see assets/css/pets-anim.css).
 *
 * @param string $class  extra classes, e.g. 'pet-mark--header'
 * @param bool   $draw   play the line-drawing intro animation
 */
function pet_mark(string $class = '', bool $draw = true, string $label = 'Something About Pets'): string
{
    $cls = trim('pet-mark ' . ($draw ? 'pet-mark--draw ' : '') . $class);
    return <<<SVG
<svg class="{$cls}" viewBox="585 85 820 380" role="img" aria-label="{$label}" xmlns="http://www.w3.org/2000/svg">
  <g class="pm-lines" fill="none" stroke="currentColor" stroke-width="17" stroke-linecap="round" stroke-linejoin="round">
    <!-- cat: body, ears and the line that becomes the dog's ear -->
    <path class="pm-stroke pm-s1" pathLength="1" d="M645 402 L668 255 C676 208 686 172 697 156 C709 139 733 133 751 143 C763 151 768 168 772 181 L852 168 C861 148 876 117 893 103 C935 134 966 165 990 193"/>
    <g class="pm-dog-ear">
      <path class="pm-stroke pm-s2" pathLength="1" d="M990 193 C960 232 944 300 947 360 C949 401 975 413 1001 405 C1036 395 1051 360 1041 320 C1031 270 1010 225 990 193"/>
    </g>
    <!-- dog head -->
    <path class="pm-stroke pm-s3" pathLength="1" d="M990 193 L1036 183 C1061 136 1111 100 1171 98 C1241 98 1286 141 1301 215"/>
    <path class="pm-stroke pm-s4" pathLength="1" d="M1196 238 L1300 221"/>
    <path class="pm-stroke pm-s5" pathLength="1" d="M1155 273 C1138 279 1121 289 1113 301"/>
    <path class="pm-stroke pm-s6" pathLength="1" d="M1139 297 C1165 333 1200 365 1238 379 C1250 383 1260 384 1268 383"/>
    <path class="pm-stroke pm-s7" pathLength="1" d="M1321 305 C1318 330 1312 352 1303 369"/>
    <path class="pm-stroke pm-s12" pathLength="1" d="M1362 304 C1355 330 1342 352 1322 368 C1314 374 1306 378 1298 381"/>
    <g class="pm-tongue">
      <path class="pm-stroke pm-s8" pathLength="1" d="M1266 386 C1262 405 1276 432 1295 444 C1312 453 1330 440 1333 420 C1335 405 1332 393 1326 386"/>
      <path class="pm-stroke pm-s13" pathLength="1" d="M1304 395 C1306 405 1307 414 1306 424"/>
    </g>
    <!-- cat face -->
    <path class="pm-stroke pm-s9" pathLength="1" d="M782 247 C785 268 806 272 816 254 C826 272 849 268 852 244"/>
    <path class="pm-stroke pm-s10" pathLength="1" d="M603 290 L690 300"/>
    <path class="pm-stroke pm-s11" pathLength="1" d="M607 346 L697 345"/>
  </g>
  <g class="pm-fills" fill="currentColor">
    <path class="pm-nose" d="M1262 225 C1300 218 1350 214 1380 218 C1392 220 1393 232 1392 245 C1390 265 1383 288 1368 301 L1291 304 C1273 303 1262 291 1259 276 C1256 259 1256 239 1262 225Z"/>
    <g class="pm-dog-eyes"><ellipse cx="1134" cy="186" rx="17" ry="23"/><ellipse cx="1221" cy="183" rx="17" ry="23"/></g>
    <g class="pm-cat-eyes"><ellipse cx="723" cy="262" rx="8" ry="12"/><ellipse cx="916" cy="236" rx="8" ry="12"/></g>
    <circle cx="1237" cy="311" r="7"/><circle cx="1264" cy="333" r="7"/><circle cx="1227" cy="342" r="7"/>
  </g>
</svg>
SVG;
}
