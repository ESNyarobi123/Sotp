<?php

test('home page renders successfully', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});
