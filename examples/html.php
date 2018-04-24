<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ChromeDevtoolsProtocol\Context;
use ChromeDevtoolsProtocol\Instance\Launcher;
use ChromeDevtoolsProtocol\Model\Page\PrintToPDFRequest;
use ChromeDevtoolsProtocol\Model\Page\SetDocumentContentRequest;

@unlink(__DIR__ . '/../test.pdf');

// context creates deadline for operations
$ctx = Context::withTimeout(Context::background(), 30 /* seconds */);

// launcher starts chrome process ($instance)
$launcher = new Launcher();
$instance = $launcher->launch($ctx);

try {
	// work with new tab
	$tab = $instance->open($ctx);
	$tab->activate($ctx);

	$devtools = $tab->devtools();
	try {
        $devtools->page()->enable($ctx);
        $frameId = $devtools->page()->getFrameTree($ctx)->frameTree->frame->id;
        $devtools->page()->setDocumentContent($ctx, SetDocumentContentRequest::fromJson((object) [
            'frameId' => $frameId,
            'html' => '<!DOCTYPE html><html><head></head><body><h1>Hello World</h1></body></html>'
        ]));
		$devtools->page()->awaitLoadEventFired($ctx);
        $data = $devtools->page()->printToPDF($ctx, PrintToPDFRequest::fromJson((object) [
            'displayHeaderFooter' => false
        ]))->data;
        file_put_contents(__DIR__ . '/../test.pdf', base64_decode($data));
	} finally {
		// devtools client needs to be closed
		$devtools->close();
	}

} finally {
	// process needs to be killed
	$instance->close();
}