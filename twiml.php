<?php

	// tell the caller that they should listen to their howl
	// and play the recording back, using the URL that Twilio posted
	header("content-type: text/xml");
	echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
	<Say>Hi - This is a call from MobStar to verify your device.Your unique code is:</Say>
	<Pause length="2"/>	
	<Say>1 2 3 4</Say>
</Response>
