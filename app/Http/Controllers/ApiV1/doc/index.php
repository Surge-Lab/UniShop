<?php

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="独角数卡 API V1",
 *     description="独角数卡项目的API V1版本，提供了RESTful风格的JSON API接口",
 *     @OA\Contact(
 *         email="Ashang@utf8.hk",
 *         name="独角数卡开发团队",
 *         url="https://utf8.hk"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * @OA\SecurityScheme(
 *      securityScheme="BearerAuth",
 *      type="http",
 *      scheme="bearer",
 *      bearerFormat="JWT",
 *      description="Bearer Token认证"
 *  )
 *
 * @OA\Server(
 *     url="https://shoptest1.surgelab.io",
 *     description="测试环境"
 * )
 *
 * @OA\Schema(
 *    schema="PaginationResponse",
 *    @OA\Property(property="current_page", type="integer", example="1", description="当前页码"),
 *    @OA\Property(property="per_page", type="integer", example="20", description="每页显示数量"),
 *    @OA\Property(property="total", type="integer", example="21", description="总条数"),
 *    @OA\Property(property="last_page", type="integer", example="2", description="最后一页")
 * )
 *
 */