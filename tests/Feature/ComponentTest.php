<?php

it("can be ran", function () {
    $this->artisan("stubs")->assertExitCode(0);
});
