<?php
declare(strict_types=1);
namespace StageArtCore\Presentation\Rest;
use WP_REST_Request;use WP_REST_Response;
final class HealthController{public function register_routes():void{register_rest_route('stageart/v1','/health',['methods'=>'GET','callback'=>[$this,'health'],'permission_callback'=>'__return_true']);}public function health(WP_REST_Request$request):WP_REST_Response{return new WP_REST_Response(['ok'=>true,'plugin'=>'StageArtCore','version'=>STAGEART_CORE_VERSION,'stageart_model'=>['member'=>'StageArtCore member','production'=>'stageart_production','performance'=>'StageArtCore production performance']]);}}
