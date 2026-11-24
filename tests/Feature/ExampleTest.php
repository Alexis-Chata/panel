<?php

it('redirects the home page to the panel', function () {
    $this->get('/')->assertRedirect(route('panel'));
});
