<?php

// Nanime
$router->group(['prefix' => 'N/1', 'namespace' => 'V1\Nanime'], function () use ($router){
    #List Anime
    $router->get('testingList', 'ListAnimeController@testing');
    $router->post('ListAnime', 'ListAnimeController@ListAnime');
    $router->post('DetailAnime', 'DetailListAnimeController@DetailListAnim');
    $router->post('SearchAnime', 'SearchAnimeControoler@SearchAnime');
    $router->post('LastUpdateAnime', 'LastUpdateEpsAnimController@LastUpdateAnime');
    $router->post('StreamAnime', 'StreamAnimeController@StreamAnime');
    $router->post('GenreListAnime', 'GenreListAnimeController@GenreListAnime');
    $router->post('SearchGenreAnime', 'SearchGenreAnimeController@SearchGenreAnime');
    $router->post('TrandingWeekAnime', 'TrandingWeekAnimeController@TrandingWeekAnime');
    $router->post('ScheduleAnime', 'ScheduleAnimeController@ScheduleAnime');
    $router->post('RecomendationAnime', 'RecomendationAnimeController@RecomendationAnime');
    $router->post('TopAnime', 'TopAnimeController@TopAnime');
    $router->post('SliderAnime', 'SliderAnimeController@SliderAnime');
});

