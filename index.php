<?php
/* query2email converts a query, predictably from query2form and then submits
   both party A and party B for them to have a dicussion.

 */

include_once '../lib/shared.php';
include_once '../lib/email.php';
include_once '../lib/header.php';
?>

<?php

foreach ($input_errors as $key => $error)
{
    echo $error;
}

if ( ! $requirements_met )
{
    echo "<h4>REQUIREMENTS NOT MET</h4>\n";
    echo "</div></body></html>";
    exit;
}

// guess at what is the from field
$from = '';
if ( !empty($inputs['email']) )
   $from = $inputs['email'];
elseif ( !empty($inputs['email-address']) )
   $from = $inputs['email-address'];
elseif ( !empty($inputs['from']) )
   $from = $inputs['from'];

if ( empty($configs['_time']) )
    $configs['_time'] = time();
   

// dumper($inputs);
// dumper($configs);

foreach ( $inputs as $key => $val )
{
    $field_test = substr( $key, -2);

    switch ( $field_test )
    {
        case '_s':
            $subs=''; 
            $matches = array();
            $subs = preg_match_all("/@([\w-]+)/", $val, $matches);
            // dumper($matches);

            for ( $k = 0; $k < count($matches[0]); $k++ )
            {   
                // put your special case here to replace variables
                // this is a silly parser

                // replace all possible input vars
                if ( isset($inputs[ $matches[1][$k] ]) )
                {
                    $val = str_replace($matches[0][$k], 
                                       $inputs[$matches[1][$k]],
                                       $val);
                    $inputs[$key] = $val;
                    // dumper($val);
                }
                // replace all possible config vars
                if ( isset($configs[ $matches[1][$k] ]) )
                {
                    $val = str_replace($matches[0][$k], 
                                       $configs[$matches[1][$k]],
                                       $val);
                    $inputs[$key] = $val;
                }
                // special operationt to conver the entire output to 
                // a short url using a service, like ours
                if ( 'u2s' == $matches[1][$k] )
                {
                    $val = str_replace('@' . $matches[1][$k], '', $val);
                    // dumper($val);
                    $short_url = get_short_url( $val );
                    if ( FAlSE != $short_url) 
                        $inputs[$key] = $short_url;
                    // dumper($short_url);
                }
            }
            // dumper($inputs); 
    }
}


$body_display = '';
echo '<table class="table table-striped table-bordered table-hover">' . "\n";
foreach ( $inputs as $key => $value )
{
    // hacky fix to strip current input type selector
    $field_test = substr( $key, -2);
    if ( '_t' == $field_test || '_h' == $field_test || '_s' == $field_test )
        $key = substr( $key, 0, -2);

    $body_display .= '<tr><td style="font-weight: bold">' . ucwords(strtr($key, '-', ' ')) . '</td>' . "<td>$value</td></tr>\n";
}
echo '<h4 class="alert alert-success">' . $configs['_success'] . "</h4>\n";
echo $body_display;
echo "</table>\n";


// email construction
$body = $configs['_success'] . "\n\n";
$body_party_b = "A form has been submitted.\n\n";
foreach ( $inputs as $key => $value )
{
    // hacky fix to strip current input type selector
    $field_test = substr( $key, -2);
    if ( '_t' == $field_test || '_h' == $field_test || '_s' == $field_test )
        $key = substr( $key, 0, -2);
    $body         .= ucwords(strtr($key, '-', ' ')) . ": $value\n";
    $body_party_b .= ucwords(strtr($key, '-', ' ')) . ": $value\n";
}

// remail($to, $from, $subject, $message, $files);
// email PARTY A
mailer($configs['_replyto'], $from, $configs['_subject'], $body_party_b);
// email PARTY B
mailer($from, $configs['_replyto'], $configs['_subject'], $body);


?>


<?php
include_once '../lib/footer.php';
