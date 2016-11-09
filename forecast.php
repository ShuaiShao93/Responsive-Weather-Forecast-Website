<?php if(isset($_GET["address"]) and isset($_GET["city"]) and isset($_GET["state"]) and isset($_GET["degree"])):     
    $GOOGLE_API_KEY = "AIzaSyCxBKxShIpFVy0FdIze_ld4ISoQNrjWdc0";
    $url1 = "https://maps.googleapis.com/maps/api/geocode/xml?address=".urlencode($_GET["address"]).','.urlencode($_GET["city"]).','.$_GET["state"].'&key='.$GOOGLE_API_KEY;
    $xmlFile = file_get_contents($url1);
    $xmlDoc = simplexml_load_string($xmlFile);
    $loc = $xmlDoc->result->geometry->location;
    $lat = $loc->lat;
    $lng = $loc->lng;
    
    if ($_GET["degree"] == "Fahrenheit"):
        $units = "us";
        $temp_units = "&#8457;";
        $windSpd_units = " mph";
        $visibility_units = " mi";
        $pressure_units = " mb";
    elseif ($_GET["degree"] == "Celsius"):
        $units = "si";
        $temp_units = "&#8451;";
        $windSpd_units = " m/s";
        $visibility_units = " km";
        $pressure_units = " hPa";
    endif;

    $FORECAST_API_KEY = "442a1def952957db9e2fbd9ab98a2f0a";
    $url2 = "https://api.forecast.io/forecast/".$FORECAST_API_KEY."/$lat,$lng?units=$units&exclude=flags";
    $jsonFile = file_get_contents($url2);
    $json = json_decode($jsonFile,true);

    function icon_url ($icon){
        $image_url = "http://cs-server.usc.edu:45678/hw/hw8/images/";
        if($icon=="clear-day"){$image_url .= "clear.png";} 
        elseif($icon=="clear-night"){$image_url .= "clear_night.png";} 
        elseif($icon=="partly-cloudy-day"){$image_url .= "cloud_day.png";}
        elseif($icon=="partly-cloudy-night"){$image_url .= "cloud_night.png";} 
        else{$image_url .= $icon.".png";}
        
        return $image_url;
    }
    
    $output = array(array(), array(), array(), array());

    $output[3]['longitude'] = $lng;
    $output[3]['latitude'] = $lat;

    $icon = $json['currently']['icon'];
    $output[0]['icon_url'] = icon_url($icon);
    $output[0]['summary'] = $json['currently']['summary'];
    $output[0]['location'] = $_GET['city'].', '.$_GET['state'];
    $output[0]['temp'] = (int)$json['currently']['temperature'];
    $output[0]['temp_units'] = $temp_units;
    $output[0]['minTemp'] = (int)$json['daily']['data'][0]['temperatureMin'];
    $output[0]['maxTemp'] = (int)$json['daily']['data'][0]['temperatureMax'];
    
    if (isset($json["currently"]["precipIntensity"])){
        $precipIntensity=$json["currently"]["precipIntensity"]; 
        if($units == "si"){$precipIntensity /= 25.4;}
        if($precipIntensity>=0 and $precipIntensity<0.002){$precip = "None";} 
        elseif($precipIntensity>=0.002 and $precipIntensity<0.017){$precip = "Very Light";} 
        elseif($precipIntensity>=0.017 and $precipIntensity<0.1){$precip = "Light";} 
        elseif($precipIntensity>=0.1 and $precipIntensity<0.4){$precip = "Moderate";} 
        elseif($precipIntensity>=0.4){$precip = "Heavy";}
        $output[0]['precipitation'] = $precip;
    }
    else{
        $output[0]['precipitation'] = "N.A.";
    }
    
    if (isset($json["currently"]["precipProbability"])){
        $precipProbability = $json['currently']['precipProbability']*100;
        $output[0]['chanceofRain'] = (int)$precipProbability."%";
    }
    else{
        $output[0]['chanceofRain'] = "N.A.";
    }
    if (isset($json["currently"]["windSpeed"])){
        $output[0]['windSpeed'] = round($json['currently']['windSpeed'], 2).$windSpd_units;
    }
    else{
        $output[0]['windSpeed'] = "N.A.";
    }
    if (isset($json["currently"]["dewPoint"])){
    $output[0]['dewPoint'] = round($json['currently']['dewPoint'], 2).$temp_units;
    }
    else {
        $output[0]['dewPoint'] = "N.A.";
    }
    if (isset($json["currently"]["humidity"])){
        $humidity = $json['currently']['humidity']*100;
        $output[0]['humidity'] = (int)$humidity."%";
    }
    else{
        $output[0]['humidity'] = "N.A.";
    }
    if (isset($json["currently"]["visibility"])){
        $output[0]['visibility'] = round($json['currently']['visibility'],2).$visibility_units;
    }
    else{
        $output[0]['visibility'] = "N.A.";
    }

    date_default_timezone_set($json["timezone"]);
    if (isset($json["daily"]["data"][0]["sunriseTime"])){
        $sunrise_timestamp=$json["daily"]["data"][0]["sunriseTime"]; 
        $sunrise_time=date("h:i A", $sunrise_timestamp);
        $output[0]['Sunrise'] = $sunrise_time;
    }
    else{
        $output[0]['Sunrise'] = "N.A.";
    }
    if (isset($json["daily"]["data"][0]["sunsetTime"])){
        $sunset_timestamp=$json["daily"]["data"][0]["sunsetTime"]; 
        $sunset_time=date("h:i A", $sunset_timestamp);
        $output[0]['Sunset'] = $sunset_time;
    }
    else{
        $output[0]['Sunset'] = "N.A.";
    }

    for ($h = 0; $h < 24; $h ++){
        $output[1][$h] = array();
        $timestamp = $json["hourly"]["data"][$h]["time"];
        $output[1][$h]["time"] = date("h:i A", $timestamp);
        $output[1][$h]["icon_url"] = icon_url($json["hourly"]["data"][$h]["icon"]);
        if (isset($json["hourly"]["data"][$h]["cloudCover"])){
            $cloudCover = $json["hourly"]["data"][$h]["cloudCover"] * 100;
            $output[1][$h]["cloudCover"] = (int)$cloudCover . "%";
        }
        else{
            $output[1][$h]["cloudCover"] = "N.A.";
        }
        $output[1][$h]["temp"] = round($json["hourly"]["data"][$h]["temperature"], 2);
        $output[1][$h]["temp_units"] = $temp_units;
        if (isset($json["hourly"]["data"][$h]["windSpeed"])){
            $output[1][$h]["windSpeed"] = round($json["hourly"]["data"][$h]["windSpeed"], 2).$windSpd_units;
        }
        else{
            $output[1][$h]["windSpeed"] = "N.A.";
        }
        if (isset($json["hourly"]["data"][$h]["humidity"])){
            $humidity = $json["hourly"]["data"][$h]["humidity"] * 100;
            $output[1][$h]["humidity"] = (int)$humidity."%";
        }
        else{
            $output[1][$h]["humidity"] = "N.A.";
        }
        if (isset($json["hourly"]["data"][$h]["visibility"])){
            $output[1][$h]["visibility"] = round($json["hourly"]["data"][$h]["visibility"], 2).$visibility_units;
        }
        else{
            $output[1][$h]["visibility"] = "N.A.";
        }
        if (isset($json["hourly"]["data"][$h]["pressure"])){
            $output[1][$h]["pressure"] = round($json["hourly"]["data"][$h]["pressure"], 2).$pressure_units;
    }
        else{
            $output[1][$h]["pressure"] = "N.A.";
        }
    }

    for ($d = 0; $d <= 7; $d ++){
        $output[2][$d] = array();
        $timestamp = $json["daily"]["data"][$d]["time"];
        $output[2][$d]["weekday"] = date("l", $timestamp);
        $output[2][$d]["date"] = date("M d", $timestamp);
        $output[2][$d]["icon_url"] = icon_url($json["daily"]["data"][$d]["icon"]);
        $output[2][$d]["minTemp"] = (int)$json["daily"]["data"][$d]["temperatureMin"];
        $output[2][$d]["maxTemp"] = (int)$json["daily"]["data"][$d]["temperatureMax"];
        $output[2][$d]["city"] = $_GET["city"];
        $output[2][$d]["summary"] = $json["daily"]["data"][$d]["summary"];
        
        if (isset($json["daily"]["data"][$d]["sunriseTime"])){
            $sunrise_timestamp=$json["daily"]["data"][$d]["sunriseTime"]; 
            $sunrise_time=date("h:i A", $sunrise_timestamp);
            $output[2][$d]['Sunrise'] = $sunrise_time;
        }
        else{
            $output[2][$d]['Sunrise'] = "N.A.";
        }
        if (isset($json["daily"]["data"][$d]["sunsetTime"])){
            $sunset_timestamp=$json["daily"]["data"][$d]["sunsetTime"]; 
            $sunset_time=date("h:i A", $sunset_timestamp);
            $output[2][$d]['Sunset'] = $sunset_time;
        }
        else{
            $output[2][$d]['Sunset'] = "N.A.";
        }
        
        if (isset($json["daily"]["data"][$d]["humidity"])){
            $humidity = $json["daily"]["data"][$d]["humidity"] * 100;
            $output[2][$d]["humidity"] = (int)$humidity."%";
        }
        else{
            $output[2][$d]["humidity"] = "N.A.";
        }
        if (isset($json["daily"]["data"][$d]["windSpeed"])){
            $output[2][$d]["windSpeed"] = round($json["daily"]["data"][$d]["windSpeed"], 2).$windSpd_units;
        }
        else{
            $output[2][$d]["windSpeed"] = "N.A.";
        }
        if (isset($json["daily"]["data"][$d]["visibility"])){
            $output[2][$d]["visibility"] = round($json["daily"]["data"][$d]["visibility"], 2).$visibility_units;
        }
        else{
            $output[2][$d]["visibility"] = "N.A.";
        }
        if (isset($json["daily"]["data"][$d]["pressure"])){
            $output[2][$d]["pressure"] = round($json["daily"]["data"][$d]["pressure"], 2).$pressure_units;
        }
        else{
            $output[2][$d]["pressure"] = "N.A.";
        }
    }

    echo json_encode($output);

else: ?>
<head>
    <title>Forecast</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style type="text/css">
        body{
            background-image:url("http://cs-server.usc.edu:45678/hw/hw8/images/bg.jpg");
            background-size: 100%;
        }
        h1{
            text-align: center;
        }
        .error{
            color:red;
        }
        div#top{
            color:white;
        }
        div.panel{
            padding : 15px;
            background-color: rgba(0, 0, 0, 0.2);
        }
        #input div, #error div, #power div{
            padding-left : 3px;
            padding-right : 3px;
        }
        #power div{
            float: right;
        }
        div#top .row{
            margin-left: -3px;
            margin-right: -3px;
        }
        #top #btn{
            float: right;
        }
        #top #btn .btn{
            display: inline-block;
        }
        #bottom #tablist a{
            background-color: rgba(238,238,238,1);
        }
        #bottom #tablist a:focus, #bottom #tablist a:hover{
            color: rgb(238,238,238);
            background-color: rgba(48,113,169,1);
        }
        #bottom #tablist li.active > a{
            color: rgb(238,238,238);
            background-color: rgba(48,113,169,1);
        }
        #bottom #current #forecast{
            padding-right: 0px;
        }
        #bottom #current #map{
            padding-left: 0px;
        }
        #bottom #head_banner{
            width: 100%;
            padding-left: 15px;
            padding-right: 15px;
            background-color: rgb(241,128,129);
        }
        #bottom #head_banner #icon{
            padding-top: 15px;
            padding-bottom: 15px;
        }
        #bottom #head_banner #cur_temp{
            color: white;
        }
        #bottom #div_table{
            width: 100%;
            background-color: rgb(242,222,222);
        }
        #bottom #hourly td, #bottom #hourly th{
            text-align: center;
        }
        #bottom #hourly #hour_th{
            color: white;
            background-color: rgb(48,113,169);
        }
        #bottom #hourly .hour_tr{
            background-color: white;
        }
        #bottom #hourly .glyphicon-plus{
            color: rgba(48,113,169,1);
        }
        #bottom #hourly .collapse_table{
             background-color:transparent;
        }
        #bottom #hourly .collapse_th{
             background-color:white;
        }
        #bottom #daily .day_div{
            color: white;
            background-color: black;
            text-align: center;
        }
        #bottom #daily .panel{
            font-size: 18px;   
            padding: 0px;
            margin-top: 15px;
            margin-bottom: 15px;
            margin-right: 15px;
        }
        #bottom #daily #day_1{
            background-color: rgb(54,125,181);
        }
        #bottom #daily #day_2{
            background-color: rgb(236,68,68);
        }
        #bottom #daily #day_3{
            background-color: rgb(230,142,79);
        }
        #bottom #daily #day_4{
            background-color: rgb(167,164,57);
        }
        #bottom #daily #day_5{
            background-color: rgb(151,112,167);
        }
        #bottom #daily #day_6{
            background-color: rgb(243,124,126);
        }
        #bottom #daily #day_7{
            background-color: rgb(206,69,113);
        }
        #bottom #daily #day_1_summary{
            color: rgb(54,125,181);
        }
        #bottom #daily #day_2_summary{
            color: rgb(236,68,68);
        }
        #bottom #daily #day_3_summary{
            color: rgb(230,142,79);
        }
        #bottom #daily #day_4_summary{
            color: rgb(167,164,57);
        }
        #bottom #daily #day_5_summary{
            color: rgb(151,112,167);
        }
        #bottom #daily #day_6_summary{
            color: rgb(243,124,126);
        }
        #bottom #daily #day_7_summary{
            color: rgb(206,69,113);
        }
    </style>
    <script  src="https://code.jquery.com/jquery.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" integrity="sha512-dTfge/zgoMYpP7QbHy4gWMEGsbsdZeCXz7irItjcC3sPUFtf0kuFbDz/ixG7ArTxmDjLXDmezHubeNikyKGVyQ==" crossorigin="anonymous">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js" integrity="sha512-K1qjQ+NcF2TYO/eI3M6v8EiNYZfA95pQumfvcVrTHtwQVDG+aHRqLi/ETn2uB+1JqwYqVG3LIvdm9lj6imS/pQ==" crossorigin="anonymous"></script>
    <script src="http://ajax.aspnetcdn.com/ajax/jquery.validate/1.14.0/jquery.validate.js"></script>
    <script src="http://openlayers.org/api/OpenLayers.js"></script>
</head>
        
<body>
    <h1>Forecast Search</h1>
    <div class="container" id="top">
        <div class="panel"><form id="form">
            <div class="row" id="input">
                <div class="col-md-3 form-group">
                    <label for="address">Street Address: <font color="red">*</font></label>
                    <input type="text" id="address" name="address" class="form-control" value="Enter street address" style="color:gray" onfocus="if(value == defaultValue){value='';}style.color='black';" onblur="if(value == ''){value='Enter street address';style.color='gray';}"/>
                </div>
                <div class="col-md-2 form-group">
                    <label for="city">City: <font color="red">*</font></label>
                    <input type="text" id="city" name="city" class="form-control" value="Enter the city name" style="color:gray" onfocus="if(value==defaultValue){value='';style.color='black';}" onblur="if(value==''){value='Enter the city name';style.color='gray';}" />
                </div>
                <div class="col-md-2 form-group">
                    <label for="state">State: <font color="red">*</font></label>
                    <select id="state" name="state" class="form-control">
                        <option value="" selected=true>Select your state...</option>
                        <option value="AL">Alabama</option>
                        <option value="AK">Alaska</option>
                        <option value="AZ">Arizona</option>
                        <option value="AR">Arkansas</option>
                        <option value="CA">California</option>
                        <option value="CO">Colorado</option>
                        <option value="CT">Connecticut</option>
                        <option value="DE">Delaware</option>
                        <option value="DC">District Of Columbia</option>
                        <option value="FL">Florida</option>
                        <option value="GA">Georgia</option>
                        <option value="HI">Hawaii</option>
                        <option value="ID">Idaho</option>
                        <option value="IL">Illinois</option>
                        <option value="IN">Indiana</option>
                        <option value="IA">Iowa</option>
                        <option value="KS">Kansas</option>
                        <option value="KY">Kentucky</option>
                        <option value="LA">Louisiana</option>
                        <option value="ME">Maine</option>
                        <option value="MD">Maryland</option>
                        <option value="MA">Massachusetts</option>
                        <option value="MI">Michigan</option>
                        <option value="MN">Minnesota</option>
                        <option value="MS">Mississippi</option>
                        <option value="MO">Missouri</option>
                        <option value="MT">Montana</option>
                        <option value="NE">Nebraska</option>
                        <option value="NV">Nevada</option>
                        <option value="NH">New Hampshire</option>
                        <option value="NJ">New Jersey</option>
                        <option value="NM">New Mexico</option>
                        <option value="NY">New York</option>
                        <option value="NC">North Carolina</option>
                        <option value="ND">North Dakota</option>
                        <option value="OH">Ohio</option>
                        <option value="OK">Oklahoma</option>
                        <option value="OR">Oregon</option>
                        <option value="PA">Pennsylvania</option>
                        <option value="RI">Rhode Island</option>
                        <option value="SC">South Carolina</option>
                        <option value="SD">South Dakota</option>
                        <option value="TN">Tennessee</option>
                        <option value="TX">Texas</option>
                        <option value="UT">Utah</option>
                        <option value="VT">Vermont</option>
                        <option value="VA">Virginia</option>
                        <option value="WA">Washington</option>
                        <option value="WV">West Virginia</option>
                        <option value="WI">Wisconsin</option>
                        <option value="WY">Wyoming</option>
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Degree: <font color="red">*</font></label><br/>
                    <lable class="radio-inline">
                        <input type="radio" name="degree" value="Fahrenheit" checked=true />Fahrenheit
                    </lable>
                    <label class="radio-inline">
                        <input type="radio" name="degree" value="Celsius" />Celsius
                    </label>
                </div>
                <div id="btn" class="col-md-2">
                    <br />
                    <button id="search" class="btn btn-primary">
                        <span class="glyphicon glyphicon-search" aria-hidden="true"></span> Search
                    </button>
                    <button id="clear" class="btn btn-default">
                        <span class="glyphicon glyphicon-refresh" aria-hidden="true"></span> Clear
                    </button>
                </div>
            </div>
            <div class="row" id="power">
                <div class="col-md-2 col-md-offset-10">
                    Powered by:<img src="http://cs-server.usc.edu:45678/hw/hw8/images/forecast_logo.png" style="max-width:100px;"/>
                </div> 
            </div></form>
        </div>
        <hr />
    </div>
    
</body>
<script>
$(function(){
    jQuery.validator.addMethod("default", function(value, element, param) {
return value != "" && value != param;}, "This field is required");
    var validator = $("#form").validate({
        rules:{
            address: {
                default:{
                    param: "Enter street address"
                }
            },
            city: {
                default:{
                    param: "Enter the city name"
                }
            },
            state: {
                required: true
            }
        },
        messages: {
            address: {
                default: "Please enter the street address"
            },
            city: {
                default: "Please enter the city"
            },
            state: {
                required: "Please select a state"
            }
        },
        submitHandler: function(form) {
            submit();
        }
    });
    
    function submit() {
        var address = $("#address").val();
        var city = $("#city").val();
        var state = $("#state").val();
        var degree = $("input[type=radio][name=degree]:checked").val();
        $.ajax({
            //url: "http://ss570hw-env.elasticbeanstalk.com",
            url: "<?php echo $_SERVER['PHP_SELF'];?>",
            data:{
                "address": address,
                "city": city,
                "state": state,
                "degree": degree
            },
            type: "GET",
            success: function(output){
                //document.write(output);
                var data = $.parseJSON(output);
                show_result(data);
            },
            error: function(xhr){
                alert("An error occured: " + xhr.status + " " + xhr.statusText);
            }
        });
    }
    
    function show_result(data){
        var result_html = "   \
            <div class='container' id='bottom'> \
                <div id='tab_div' style='width:100%'>  \
                    <ul class='nav nav-tabs' id='tablist'>  \
                        <li class='active'><a data-toggle='tab' href='#current'>Right Now</a></li> \
                        <li><a data-toggle='tab' href='#hourly'>Next 24 Hours</a></li>  \
                        <li><a data-toggle='tab' href='#daily'>Next 7 Days</a></li> \
                    </ul>   \
                </div>  \
                <div id='main' class='tab-content' style='width:100%'>  \
                    <div class='tab-pane fade in active' id='current' style='width:100%'>  \
                        <div class='row'> \
                            <div class='col-md-6' id='forecast'>    \
                                <div id='head_banner'>   \
                                    <div class='row'>   \
                                        <div class='col-md-4 col-md-offset-1' id='icon'>  \
                                            <img src="+data[0]['icon_url']+" class='img-responsive' style='display:block;margin:auto;'/> \
                                        </div>  \
                                        <div class='col-md-6 col-md-offset-1' id='cur_temp'>  \
                                            <div class='row'>   \
                                                <div class='col-md-12'> \
                                                    <p class='text-center' style='margin-bottom:0px'><strong>"+data[0]['summary']+" in "+data[0]['location']+"</strong></p>   \
                                                </div>  \
                                            </div>  \
                                            <div class='row'>   \
                                                <div class='col-md-12'> \
                                                    <p class='text-center'><strong style='font-size:80px'>"+data[0]['temp']+"</strong><sup style='font-size:40px'>"+data[0]['temp_units']+"</p></h1>   \
                                                </div>  \
                                            </div>  \
                                            <div class='row'>   \
                                                <div class='col-md-10'> \
                                                    <p class='text-center'><font color='blue'>L: "+data[0]['minTemp']+"&#176;</font><font color='black'> | </font><font color='green'>H: "+data[0]['maxTemp']+"&#176;</font></p>  \
                                                </div>  \
                                                <div class='col-md-2'>  \
                                                    <img src='http://cs-server.usc.edu:45678/hw/hw8/images/fb_icon.png' id='fb_icon' style='float:right;max-width:30px;'/>  \
                                                </div>  \
                                            </div>  \
                                        </div>  \
                                    </div>  \
                                </div>  \
                                <div id='div_table'>    \
                                    <table class='table table-striped table-responsive'>    \
                                        <tr>    \
                                            <td>Precipitation</td>  \
                                            <td>"+data[0]['precipitation']+"</td> \
                                        </tr>   \
                                        <tr>    \
                                            <td>Chance of Rain</td> \
                                            <td>"+data[0]['chanceofRain']+"</td>   \
                                        </tr>   \
                                        <tr>    \
                                            <td>Wind Speed</td> \
                                            <td>"+data[0]['windSpeed']+"</td>   \
                                        </tr>   \
                                        <tr>    \
                                            <td>Dew Point</td> \
                                            <td>"+data[0]['dewPoint']+"</td>   \
                                        </tr>   \
                                        <tr>    \
                                            <td>Humidity</td> \
                                            <td>"+data[0]['humidity']+"</td>   \
                                        </tr>   \
                                        <tr>    \
                                            <td>Visibility</td> \
                                            <td>"+data[0]['visibility']+"</td>   \
                                        </tr>   \
                                        <tr>    \
                                            <td>Sunrise</td> \
                                            <td>"+data[0]['Sunrise']+"</td>   \
                                        </tr>   \
                                        <tr>    \
                                            <td>Sunset</td> \
                                            <td>"+data[0]['Sunset']+"</td>   \
                                        </tr>   \
                                    </table>    \
                                </div>  \
                            </div>  \
                            <div class='col-md-6' id='map'> \
                                <div id='basicMap'> \
                                </div>  \
                            </div>  \
                        </div>  \
                    </div>  \
                    <div id='hourly' class='tab-pane fade'> \
                        <table class='table table-responsive' align='center'>   \
                            <tr id='hour_th'>    \
                                <th>Time</th>   \
                                <th>Summary</th>    \
                                <th>Cloud Cover</th>    \
                                <th>Temp ("+data[1][0]['temp_units']+")    \
                                <th>View Details</th>   \
                            </tr>";
                        for (var h=0;h<24;h++){
                            result_html += "    \
                            <tr class='hour_tr'>    \
                                <td>"+data[1][h]['time']+"</td>   \
                                <td><img src='"+data[1][h]['icon_url']+"' style='max-width:30px;'/></td>    \
                                <td>"+data[1][h]['cloudCover']+"</td>   \
                                <td>"+data[1][h]['temp']+"</td> \
                                <td><a data-toggle='collapse' href='#hour_detail"+h+"'><span class='glyphicon glyphicon-plus' /></td>  \
                            </tr>   \
                            <tr class='collapse' id='hour_detail"+h+"'>  \
                                <td class='well' colspan='5' style='padding:19px;'><table class='table collapse_table table-responsive' align='center'>    \
                                    <tr class='collapse_th'>    \
                                        <th>Wind</th>   \
                                        <th>Humidity</th>   \
                                        <th>Visibility</th> \
                                        <th>Pressure</th>   \
                                    </tr>   \
                                    <tr>    \
                                        <td>"+data[1][h]['windSpeed']+"</td>  \
                                        <td>"+data[1][h]['humidity']+"</td> \
                                        <td>"+data[1][h]['visibility']+"</td>   \
                                        <td>"+data[1][h]['pressure']+"</td> \
                                    </tr>   \
                                </table></td>    \
                            </tr>   \
                            ";  
                        }
        result_html += "</table>    \
                    </div>  \
                    <div id='daily' class='tab-pane fade'>  \
                        <div  class='row day_div'>  \
                            <div class='col-md-1 col-md-offset-2 panel' id='day_1' data-toggle='modal' data-target='#day_modal_1'>   \
                                <h4>"+data[2][1]['weekday']+"</h4>    \
                                <h4>"+data[2][1]['date']+"</h4>    \
                                <img src='"+data[2][1]['icon_url']+"' style='max-width:70px'><br/>  \
                                <p>Min</p>   \
                                <p>Temp</p>  \
                                <h2>"+data[2][1]['minTemp']+"&#176;</h2> \
                                <p/>Max</p>   \
                                <p>Temp</p>  \
                                <h2>"+data[2][1]['maxTemp']+"&#176;</h2>  \
                            </div> "
                    for (var d=2;d<=7;d++){
                        result_html += "    \
                            <div class='col-md-1 panel' id='day_"+d+"' data-toggle='modal' data-target='#day_modal_"+d+"'>   \
                                <h4>"+data[2][d]['weekday']+"</h4>    \
                                <h4>"+data[2][d]['date']+"</h4>    \
                                <img src='"+data[2][d]['icon_url']+"' style='max-width:70px'><br/>  \
                                <p>Min</p>   \
                                <p>Temp</p>  \
                                <h2>"+data[2][d]['minTemp']+"&#176;</h2> \
                                <p/>Max</p>   \
                                <p>Temp</p>  \
                                <h2>"+data[2][d]['maxTemp']+"&#176;</h2>  \
                            </div>  \
                        ";
                    }
        result_html += "</div> ";
                    for (var d=1;d<=7;d++){
                        result_html += "    \
                        <div class='modal fade' id='day_modal_"+d+"'>   \
                            <div class='modal-dialog'>  \
                                <div class='modal-content'> \
                                    <div class='modal-header'>  \
                                        <button type='button' class='close' data-dismiss='modal'>&times;</button>   \
                                        <h4 class='modal-title text-left'>Weather in "+data[2][d]['city']+" on "+data[2][d]['date']+" \
                                    </div>  \
                                    <div class='modal-body'>    \
                                        <div class='row'>   \
                                            <div class='col-md-4 col-md-offset-4'>  \
                                                <img src='"+data[2][d]['icon_url']+"' class='img-responsive'>  \
                                            </div>  \
                                        </div>  \
                                        <div class='row'>   \
                                            <div class='col-md-12' id='day_"+d+"_summary'> \
                                                <h3 class='text-center'><font color='black'>"+data[2][d]['weekday']+":</font> "+data[2][d]['summary']+"</h3>    \
                                            </div>  \
                                        </div>  \
                                        <div class='row text-center'>   \
                                            <div class='col-md-4'>  \
                                                <h4>Sunrise Time</h4>   \
                                                <p>"+data[2][d]['Sunrise']+"</p> \
                                            </div>  \
                                            <div class='col-md-4'>  \
                                                <h4>Sunset Time</h4>   \
                                                "+data[2][d]['Sunset']+"   \
                                            </div>  \
                                            <div class='col-md-4'>  \
                                                <h4>Humidity</h4>   \
                                                "+data[2][d]['humidity']+"   \
                                            </div>  \
                                        </div>  \
                                        <div class='row text-center'>   \
                                            <div class='col-md-4'>  \
                                                <h4>Wind Speed</h4>   \
                                                <p>"+data[2][d]['windSpeed']+"</p> \
                                            </div>  \
                                            <div class='col-md-4'>  \
                                                <h4>Visibility</h4>   \
                                                "+data[2][d]['visibility']+"   \
                                            </div>  \
                                            <div class='col-md-4'>  \
                                                <h4>Pressure</h4>   \
                                                "+data[2][d]['pressure']+"   \
                                            </div>  \
                                        </div>  \
                                    </div>  \
                                    <div class='modal-footer'>    \
                                        <button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>   \
                                    </div>  \
                                </div>  \
                            </div>  \
                        </div> ";
                    }
    result_html += "</div>  \
                </div>  \
            </div>  \
        ";
        if ($("#bottom").length > 0){
            $("#bottom").remove();
        }
        $("body").append(result_html);
        
        $("#basicMap").css("height",document.getElementById("head_banner").offsetHeight+document.getElementById("div_table").offsetHeight + "px");
        
        var lonlat = new OpenLayers.LonLat(parseFloat(data[3]['longitude']['0']), parseFloat(data[3]['latitude']['0']));
        lonlat.transform(
		    new OpenLayers.Projection("EPSG:4326"), 
		    new OpenLayers.Projection("EPSG:900913")
	    );
        var map = new OpenLayers.Map("basicMap");
        var mapnik = new OpenLayers.Layer.OSM();
        var layer_cloud = new OpenLayers.Layer.XYZ(
            "clouds",
            "http://${s}.tile.openweathermap.org/map/clouds/${z}/${x}/${y}.png",
            {
                isBaseLayer: false,
                opacity: 0.7,
                sphericalMercator: true

            }
        );

        var layer_precipitation = new OpenLayers.Layer.XYZ(
            "precipitation",
            "http://${s}.tile.openweathermap.org/map/precipitation/${z}/${x}/${y}.png",
            {
                isBaseLayer: false,
                opacity: 0.7,
                sphericalMercator: true
            }
        );


        map.addLayers([mapnik, layer_precipitation, layer_cloud]);
        map.setCenter(lonlat, 10);
        
        (function(d, s, id){
             var js, fjs = d.getElementsByTagName(s)[0];
             if (d.getElementById(id)) {return;}
             js = d.createElement(s); js.id = id;
             js.src = "//connect.facebook.net/en_US/sdk.js";
             fjs.parentNode.insertBefore(js, fjs);
           }(document, 'script', 'facebook-jssdk'));
        
        $("#fb_icon").click(function(){
            FB.init({
              appId      : '1650426445197537',
              xfbml      : true,
              version    : 'v2.5'
            });


            FB.ui(
              {
                method: 'feed',
                picture: 'http://cs-server.usc.edu:45678/hw/hw8/images/clear.png',
                link: 'http://forecast.io',
                name: 'Current Weather in '+data[0]['location'],
                description: data[0]['summary'] +', '+data[0]['temp']+data[0]['temp_units'],
                caption: 'WEATHER INFORMATION FROM FORECAST.IO' 
              },
              // callback
              function(response) {
                if (response && !response.error_message) {
                  alert('Posted Successfully');
                } else {
                  alert('Not Posted');
                }
              }
            );
        });
    }
    
    

    
    
    
    $("#clear").click(function(){
        validator.resetForm();
        $("#form .form-control").removeClass("error");
        $("#address").val("Enter street address").attr("style","color:gray");
        $("#city").val("Enter the city name").attr("style","color:gray");
        $("#state").val("").attr("selected", true);
        $("input[type=radio][name=degree][value='Fahrenheit']").prop("checked", true);
        $("#bottom").remove();
        
        return false;
    });
});
</script>
<?php endif; ?>