<?php
namespace Catapult;

/**
 * Below are all the models listed. These
 * should all perform asimilar functions
 * and be able to provide get, create and 
 * other utils according to their type.
 *
 *
 * List of models:
 * @class Bridge, BridgeCollection
 * @class Call, CallCollection
 * @class Conference, ConferenceCollection
 * @class ConferenceMember,
 * @class Recording, Recording Collection
 * @class Message, MessageMulti, MessageCollection
 * @class Gather, GatherCollection
 * @class PhoneNumbers, PhoneNumbersCollection
 * @class Application, ApplicationCollection
 * @class UserError, UserErrorCollection
 * @class NumberInfo
 * @class Media
 *
 */
final class Bridge Extends AudioMixin {
	private $path = "bridges";

	/* valid fields */
	public static $fields = array(
	       'audio', 'completed_time', 'created_time', 'activated_time'
	);

        /**
	 * Data we need to consider
         * validate  @object Bridge
         *
         */
	public static $needs = array(
		'id'
	);

	/**
	 * Bridge call CTor accept calls 
	 * as main argument
	 * add bridge_audio from args
         *
	 * @param $calls -> list of calls
	 * @param $args -> additional arguments
	 */
	public function __construct($data=null)
	{
		$this->client = Client::get();

		return Resolver::Find($this, $data);
	}

	/**
	 * List all the bridges
	 * @param $page -> Catapult Page 
	 * @param $size -> Catapult size
	 */
	public function list_bridges($page=0, $size=1000)
	{
		$url = URIResource::Make($this->path);

		$data = new DataPacket(array("page" => $page, "size" => $size));

		return new BridgeCollection(new DataPacketCollection($this->client->get($url, $data->get())));
	}

	/**
	 * get all the calls assigned to this
	 * bridge. This will by default return
         * a collection to get the ids
	 *
         * @param object -> plain
	 */
	public function get_calls()
	{
		$url = URIResource::Make($this->path, array($this->id, "calls"));
		$res = $this->client->get($url);
	
		return new CallCollection(new DataPacketCollection($res));
	}

	/**
	 * Get a bridge by a singular id 
         * 
         *
	 * @param $bridge_id -> valid bridge id
	 */
	public function get($bridge_id)
	{
		$url = URIResource::Make($this->path, array($bridge_id)); 

		$data = new DataPacket($client->get($url));

		return Constructor::Make($this, $data->get());
	}

	/**
	 * Create a bridge
	 * given a set of calls.
         * Where calls can be provided
         * as an associative array, list
         * or set of call objects
         *
	 * @param $args -> list of call ids and options 
	 */
	public function create($args)
	{
		$data = Ensure::Input($args);

		$url = URIResource::Make($this->path);

		$id = Locator::Find($this->client->post($url, $data->get()));

		$data->add("id", $id);

		return Constructor::Make($this, $data->get());
	}

	/**
	 * Add another
	 * to a call bridge
	 *
	 * @param $caller -> PhoneNumber
	 * @param $callee -> PhoneNumber
	 * @param $args -> Call's arguments (see in function)
	 */
	public function call_party($caller, $callee, $args)
	{
		$new_call = Call::create($caller, $callee, $this->id, $args);

		$this->calls ++; 

		return Constructor::Make($this, $data->get());
	}

	/** 
	 * Return all the call ids
	 * for a given bridge
	 */
	public function call_ids()
	{
		$call_ids = array();	

		foreach ($this->calls as $call)
			$call_ids[] = $call['call_id'];

		return $call_ids;
	}

	/**
	 * Update the bridge
	 * with new information
	 * @param $calls -> list of calls
	 * @param $data -> data to pass
	 */
	public function update($calls, $data)
	{
		$url = URIResource::Make($this->path, array($this->id));
	
		$data = new DataPacket($data);

		$this->client->post($url, $data->get());

		$this->calls = $calls;

		return Constructor::Make($this, $data->get());
	}

	/**
	 * Fetch all the calls
	 * from a bridge
	 * 
	 * @return -> list of calls
	 *
	 */
	public function fetch_calls()
	{
		$url = URIResource::Make($this->path, array($this->id, "calls"));
	
		$res = $this->client->get($url);	

		$this->calls = new CallCollection(new DataPacketCollection($res));

		return $this->calls;
	}

	/**
	 * Get the audio url for a bridge
	 *
	 * @return -> fully qualified url
	 */
	public function get_audio_url()
	{
		return URIResource::Make($this->path, array($this->id, "audio"));
	}

	/**
	 * Refresh a call bridge
	 */
	public function refresh()
	{
		$url = URIResource::Make($this->path, array($this->id));
		$data = new DataPacket($this->client->get($url));
	
		$this->set_up($data->get());	
	}
}


final class Call Extends AudioMixin {
	private $path = "calls";

        public static $primary_id = "call_id";
	public static $primary_method = "get";
	public static $primary_init = "id";
        
	public static $fields = array(
			'callId', 'direction', 'from', 'to', 'recordingEnabled', 'callbackUrl',
                         'state', 'startTime', 'activeTime', 'bridgeId'
	);
	public static $needs = array(
			'id', 'direction', 'from', 'to'	
	);

	/**
	 * construct the call as initiated  or new
         * each constructor must have a way to call itself's create/1 function with the
         * arguments
	 * @param $data -> call data
 	 */
	public function __construct($data=null)
	{

		$this->client = Client::get(realpath(__FILE__));	

		return Resolver::Find($this, $data);
	}


	/**
	 * Setup data for the call
	 * Either call with Constructor or through object
	 * @param $data -> call data array
	 */
	public function set_up($data)
	{
		if (!isset($this->from))
			$this->from = $data['from'];
		if (!isset($this->call_id))
			$this->call_id = $data['call_id'];

		return;
	}

	/**
	 * Get a call by a specific id
	 * afterwards initialize the object
         *
	 * @param call_id -> Id 
	 */
	public function get($call_id)
	{
		$url = URIResource::Make($this->path, array($call_id));

		$call = new DataPacket($this->client->get($url));
		$d = $call->get();
		$call->add("call_id", $d['id']);

		return Constructor::Make($this, $call->get());	
	}

	/**
	 * List all attempted
	 * calls. 
	 *
	 * @param $query -> list of arguments
	 */	
	public function list_calls($query = array()/* associative array */)
	{
		$data = Ensure::Input($query);

		if (!($data->has("size")))
			$data->add("size", DEFAULTS::SIZE);			
		if (!($data->has("page")))
			$data->add("page", DEFAULTS::PAGE);			


		$url = URIResource::Make($this->path);

		$calls = $this->client->get($url, $data->get());

		return new CallCollection(new DataPacketCollection($calls));
	}

	/**
	 * Initiate a call
	 * Afterwards return a new
	 * object with the call details
	 * @param $data -> polymorphic array of data satisfies input
	 * 
	 */
	public function create($data /* polymorphic */)
	{
		$data = Ensure::Input($data);
		$url = URIResource::Make($this->path);

		$callid = Locator::Find($this->client->post($url, $data->get()));

                $data->add("call_id", $callid);
                $data->add("id", $callid);

		return Constructor::Make($this, $data->get());	
	}


	/**
	 * update the call
	 *
	 * @param data -> set of data
	 */
	public function update($data)
	{
		$url = URIResource::Make($this->path, array($this->id));

		$data = Ensure::Input($data);

		$this->client->post($url, $data->get());

		return Constructor::Make($this, $data->get());
	}

	/**
	 * Transfer a call
	 * Call object MUST already be initialized
	 * or created given a Legal call id
	 * if we dont have a call id throw warning
	 *
	 * @param $phone -> phone number
 	 * @param $transfer_caller_id -> A Phone number
	 */
	public function transfer($phone, $args = array() /* polymorphic */)
	{
		$url = URIResource::Make($this->path, array($this->call_id));

		$data = Ensure::Input($args);

		$data->add("transferTo", (string) $phone);
		$data->add("state", CALL_STATES::transferring);

		$response = $this->client->post($url, $data->get());
		
		$call_id = Locator::Find($response);

		$data->add("id", $call_id);
		$data->add("call_id", $call_id);

		return Constructor::Make($this, $data->get());
	}

	/**
	 * Bridge calls
	 * forward to object bridge
         * 
         * @param $calls -> list of calls
	 * @param $args -> additional data to pass
	 */
	public function bridge($calls, $args)
	{
		return Bridge::Create($calls, $args);
	}

	/**
	 * Refresh a call id
	 * where the call id MUST
	 * be initiated like transfer
         * stub to create
         * @return void
	 */ 
	public function refresh()
	{
		$this->create(PhoneCombo::Make(new PhoneNumber($this->from), new PhoneNumber($this->to)));
	}

	/**
	 * Hangup a call
	 *
	 * needs a call id
	 * @return void
	 */
	public function hangup()
	{
		$url = URIResource::Make($this->path, array($this->call_id));

		$data = new DataPacket(States::Make(CALL_STATES::completed));
		
		$this->client->post($url, $data->get());

                return Constructor::Make($this, $data->get());
	}

        /**
	 * Accept an incoming
         * call.
         *
         * @return void
         */
        public function accept()
        {
		$url = URIResource::Make($this->path, array($this->call_id));

		$data = new DataPacket(States::Make(CALL_STATES::active));
		
		$id = Locator::Find($this->client->post($url, $data->get()));
		$data->add("id", $id);
		$data->add("call_id", $id);

                return Constructor::Make($this, $data->get());
        }

	/**
	 * wait for a call to go to any
	 * state other than 'started'
	 * @timeout a default time to wait
	 *
	 */
	public function wait($timeout=null)
	{
		$delta = time();
		if ($timeout == null)
			$timeout = 60 * 2; // two minutes

		if (!($this->check("state", "started")))
			Throw new \CatapultApiException("Call already in non 'started' state'");

		while (true)
			if (!($this->check("state", "started")))
				break;
			if ((time() - $delta) > $timeout)
				break;
	}

	/**
	 * Reject an incoming call. Call id must already be passed
	 *
	 * @return void
	 */
	public function reject()
	{
		$url = URIResource::Make($this->path, array($this->id));
		$data = new DataPacket(States::Make(CALL_STATES::rejected));

		$this->client->post($url, $data->get());

                return Constructor::Make($this, $data->get());
	}

	/**
	 * Sends a string of characters as DTMF on the given call_id
         * Valid chars are '0123456789*#ABCD'
	 *
	 * @param $dtmf -> {} 
	 */
	public function send_dtmf($dtmf)
	{
                $args = Ensure::Input($dtmf);
		//$args = func_get_args();
		//$dtmf = $args[0][0];
                $dtmf = $args->get();

		$url = URIResource::Make($this->path, array($this->call_id, "dtmf"));
		$data = new DataPacket(array("dtmfOut" => (string) $dtmf));

		$this->client->post($url, $data->get());
	}

	/**
	 * Forward to gather
	 * object and return
	 *
	 * @return Gather object with loaded call id and client
 	 */
	public function gather()
	{
		return new Gather($this->call_id, $this->client);
	}

	/* Get the recordings
	 * for a given call
	 * @return RecordingCollection
	 *
	 */
	public function get_recordings()
	{
		$url = URIResource::Make($this->path, array($this->call_id, "recordings"));

		return new RecordingCollection(new DataPacketCollection($this->client->get($url)));
	}

	/**
	 * Get all transcriptions
	 * for a call
	 * @return TranscriptionCollection
	 */
	public function get_transcriptions()
	{
		$url = URIResource::Make($this->path, array($this->call_id, "transcriptions"));

		return new TranscriptionCollection(new DataPacketCollection($this->client->get($url)));
	}

	/** 
	 * Get all the events
	 * 
	 * for the initiated
	 * call. 
	 */
	public function get_events()
	{
		$url = URIResource::Make($this->path, array($this->call_id, "events"));

		return new EventCollection(new DataPacketCollection($this->client->get($url)));
	}


        /**
	 * Check whether call 
         * convenience around direction where
         * value is "in" | "out"
         */
	public function is_incoming()
	{
		return ($this->direction == "in");
	}

	/**
	 * opposite of is_incoming/1
	 * no need to create its own version
	 */
	public function is_outgoing()
	{
		return !($this->is_incoming());
	}

	/**
	 * overloads existing
	 * get_audio_url/1
	 */
	public function get_audio_url()
	{
		return URIResource::Make($this->path, array($this->call_id, "audio"));
	}
	
}


class Gather extends GenericResource {
	private $path = "calls";

	public static $needs = array(
		"id"
	);

	/**
	 * CTor for gather resource. Needs call id point to the same client
	 * used in previous instance
	 */
	public function __construct($call_id=null, $client=null)
	{
		if (is_object($call_id))
			$this->call_id = $call_id->id;
		else
			$this->call_id = $call_id;

		if (isset($client) && $client !== null)
			$this->client = $client;
		else
			$this->client = Client::get();
	}

	/**
	 * set up a gather with a given gather id
	 * @param $data -> data
	 */
	public function set_up($data)
	{
		if (isset($data['id']))
			$this->id = $data['id'];
	}

	/**
	 * Get the gather DTMF parameters and results
         *
	 * @param gather_id
	 * @return Gather instance
	 */
	public function get($gather_id)
	{
		$url = URIResource::Make($this->path, array($this->call_id, "gather", $gather_id));

		$data = new DataPacket($this->client->get($url));

		$data->add("id", $gather_id);

		return Constructor::Make($this, $data->get());
	}

	/**
	 * Create a gather  given a set of arguments  this should
	 * return the same instance with an id being set
	 *
	 * @param $args -> gather args
	 */
	public function create($args)
	{
		$data = Ensure::Input($args);

		$url = URIResource::Make($this->path, array($this->call_id, "gather"));
		$response = $this->client->post($url, $data->get());

		$this->id = Locator::find($response);

		$data->add("add", $this->id);

		return Constructor::Make($this, $data->get());
	}

	/**
	 * Update the gather DTMF
	 * The only update allowed is state:completed
 	 * to stop the gather
	 *
	 * @return void [needs ID]
	 */
	public function stop()
	{
		$url = URIResource::Make($this->path, array($this->call_id, "gather", $this->id));

		$data = new DataPacket(array("state" => GATHER_STATES::completed));

		$this->client->post($url, $data->get());

		return COnstructor::Make($this, $data->get());
	}
}

/**
 * Conference class
 * methods for creating, updating
 * and deleting a conference. Implementation
 * like Python Version.
  */
final class Conference extends AudioMixin {
	private $path = "conferences";

	private $fields = array(
			'id', 'state', 'from', 'created_time', 'completed_time', 'fallback_url',
                        'callback_timeout', 'callback_url', 'active_members'
	);

	public static $primary_method = "create";
	public static $primary_init = "from";

	public static $needs = array(
		'id', 'state', 'from'
	);

	public function __construct($data=null)
	{
		$this->client = Client::get();

		return Resolver::Find($this, $data);
	}

	
	/**
	 * Setup the conference
	 * according to provided
	 * data['from'] or not
	 * @param $data -> conference data
	 */
	public function set_up($data)
	{
		if (isset($data['from']))
			$this->from = $data['from'];

		if (isset($data['id']))
			$this->id = $data['id'];
	}

	/**
	 * Get the audio url for conference
	 *
	 * @return -> fully qualified url
	 */
	public function get_audio_url()
	{
		return URIResource::Make($this->path, array($this->id));
	}


	/**
	 * Create a conference
	 *
	 * @param $from -> PhoneNumber
	 * @param $args -> conference info
	 */
	public function create($args)
	{
		$data = Ensure::Input($args);

		$this->id = Locator::find($this->client->post($this->path, $data->get()));
	
		$data->add("id", $this->id);	

		return Constructor::Make($this, $data->get());
	}

	/**
	 * Get a conference by id
	 *
	 * @param $id -> CatapultId
	 */
	public function get($id)
	{
		$url = URIResource::Make($this->path, array($id));

		$data = new DataPacket($this->client->get($url));

		return Constructor::Make($this, $data->get());
	}

	/**
	 * Update the conference
	 * with new information
	 *
	 * @param $params -> set of arguments
	 * @return void HTTP 201 
	 */
	public function update($params)
	{
		$data = Ensure::Input($params);

		$url = URIResource::Make($this->path, array($this->id));

		$this->client->post($url, $data->get());

		return Constructor::Make($this, $data->get());
	}

	/**
	 * Get all the members
	 * inside a conference. 
	 * Needs to be initiated
	 * @return ConferenceMemberCollection
	 *
	 */
	public function get_members()
	{
		$url = URIResource::Make($this->path, array($this->id, "members"));

		$members = new ConferenceMemberCollection(new DataPacketCollection($this->client->get($url)));

		return $members;
	}

	/**
	 * Add a member inside
	 * a conference
	 *
	 * @param $call_id -> Catapult id
	 * @param $params -> List of member parameters
	 *       joinTone
         *       leavingTone
	 */
	public function add_member($params)
	{
		$args = Ensure::Input($params);

		$url = URIResource::Make($this->path, array($this->id, "members"));

		$member_id = Locator::Find($this->client->post($url, $args->get()));

		return $this->member($member_id);
	}

	/**
	 * point to a
	 * member and update
	 *
	 * @param params -> set of arguments with
  	 * with memberId
	 */
	public function update_member($args)
	{
		$args = Ensure::Input($args);

		$url = URIResource::Make($this->path, array($this->id, "members", $args->get("memberId")));

		$this->client->post($url, $args->get());
	}

	/**
	 * Return a partial for
	 *
	 * the member selected
	 */
	public function member()
	{
		return new ConferenceMember($this->id);
	}
}

/* Represent a member in a conference
 * methods to get audio files, get member
 * and update
 */
final class ConferenceMember extends AudioMixin {
	private $path = "members";
	private $fields = array(
		'id', 'state', 'added_time', 'hold', 'mute', 'join_tone', 'leaving_tone'
	);
	public static $needs = array(
		'id', 'state', 'from'
	);

	public static $primary_method = "get";

	/**
	 * CTor for conference memebrs 
	 * NEEDS conference id
	 *
	 * @param $conf_id -> conf_id
	 * @param $data -> additional data
	 */
	public function __construct($conf_id=null, $data=null)
	{
		$this->client = Client::Get();
		$this->conf_id = $conf_id;

		return Resolver::Find($this, $data);
	}

        /**
	 * Update a given conference members
         * with new attributes.
         *
         *
         * @param args -> list of valid args
         */
        public function update($args)
        {
               $data = Ensure::Input($args);

               $url = URIResource::Make($this->conf_id, array($this->id));

               $this->client->post($url, $data->get());

               return Constructor::Make(self::__construct, $data->get()); 
        }		

	/**
	 * Get a conference member
	 * Needs to be initialized
	 *
	 * @param id -> valid member id | defaults to object's id
	 */
	public function get($id=null)
	{
		$url = URIResource::Make($this->path, $this->id);

		$data = new DataPacket($this->client->get($url));

		return Constructor::Make($this, $data->get());
	}

	/**
	 * Get audio url
 	 * for conference member
	 * 
	 */
	public function get_audio_url()
	{
		return URIResource::Make($this->path, array($this->conf_id, "members", "audio"));
	}
}

/**
 * Plural form of 
 * conference members
 *
 */
final class ConferenceMemberCollection {

	public function getName()
	{
		return "ConferenceMember";
	}
	/**
	 * Plural form
	 * of conference
	 * members
	 * @param $data -> raw member objects
 	 */
	
}


final class Recording extends GenericResource {

	private $path = "recordings";
	public  $primary_method = "get";


	public static $needs = array(
		"id"
	);

	/**
	 * CTor for recording
	 * accept either 
 	 * an existing recording
	 * or new
	 * @param $data-> [Array or DataPacket]
	 */
	public function __construct($data=null)
	{
		$this->client = Client::Get();

		return Resolver::Find($this, $data);
	}

	/**
	 * Set up recording
	 * object
	 * @param $data -> [Array or DataPacket]
 	 */
	public function set_up($data)
	{
		$call = $data['call'];
		unset($data['call']);

		if ($call)
			$data['call'] = $call;

		return Constructor::Make(self::__construct, $data);
	}

	/**
	 * List all the recordings
	 * return as RecordingCollection
	 * @param page -> [CatapultPage or Int]
	 * @param size -> [CatapultSize or int]
 	 */
	public function list_recordings($args)
	{
		$data = Ensure::Input($args);

		if (!($data->has("size")))
			$data->add("size", DEFAULTS::SIZE);			
		if (!($data->has("page")))
			$data->add("page", DEFAULTS::PAGE);			

		$url = URIResource::Make($this->path);
		
		$res = $this->client->get($url, $data->get());

		return new RecordingCollection(new DataPacketCollection($res));
	}

	/**
	 * Get a recording by id
	 * 
	 * @param $recording_id -> id
 	 *
	 */
	public function get($recording_id)
	{
		$url = URIResource::Make($this->path, array($recording_id));

		$data = new DataPacket($this->client->get($url));
		
		return Constructor::Make($this, $data->get());
	}

	/**
	 * Download a media file
	 * provided the content type
	 * on successful response of content type
	 * header
	 */	
	public function get_media_file()
	{
		$content = $this->client->get($this->media, array(), FALSE);

		return new Media($content, $this->media);
	}
}


/**
 * Pointer to Catapult
 * media. Should be used
 * in conjuction with PoolResource
 */
final class Media extends GenericResource {
	private $path = "media";
	public $primary_method = "get";
	public $primary_init = "get";

	/**
	 * Construct a media object
	 * where data must be a blob
	 * in binary. Store in memory until
	 * store/1 is called
	 * if data is passed use this as object
	 * otherwise initialize from passed id
         *
	 * @param data -> data blob
	 * @param id -> media id
	 */
	public function __construct($data=null, $id=null)
	{
		$this->data = $data;

		$this->client = Client::Get();

		if ($id) $this->id = $id;

		if ($data == null) return Resolver::Find($this, $data);
	}

	/**
	 * Stub for upload
	 */
	public function create($args)
	{ 
		return $this->upload($args);
	}

	/**
 	 * lists all the media
	 * for the user
	 *
         */
	public function list_media($args)
	{
		$url = URIResource::Make($this->path);

		return new MediaCollection(new DataPacketCollection($this->client->get($url)));
	}

	/**
	 * Upload new
	 * media.
	 * @param args
	 * must contain fileName and file(path to file)
	 */
	public function upload($args)
	{
		$args = Ensure::Input($args);
		$data = $args->get();

		$url = URIResource::Make($this->path, array($data["mediaName"]));
		$file = FileHandler::Read($data['file']);

		return $this->client->put($url, $file);
	}

	/**
	 * Store media as file 
	 * on the fs
	 *
         * By default create a directory if not
         * currently available
         *
	 * @param $filename -> full file name
         * @param $extention -> extention to save in 
	 */
	public function store($filename, $filext=DEFAULTS::EXTENSION)
	{	
		return FileHandler::save($filename, $this->data);
	}

	/**
	 * get a media
	 * file by its id
	 * @param mediaid full id
	 */
	public function get($mediaid)
	{
		$url = URIResource::Make($this->path, array($this->id));
		
		$this->client->get($url);

		return Constructor::Make($this);
	}

	/**
	 * delete a media
	 * file
         * @param mediaid
	 */
	public function delete($mediaid)
	{
		$url = URIResource::Make($this->path, array($this->id));

		$this->client->delete($url);

		return Constructor::Make($this);
	}
}

class Message extends GenericResource {
	public  $state;
	public  $id;
	private $path = "messages";

	public static $fields = array(
		'id', 'direction', 'callbackUrl', 'callbackTimeout',
                'fallbackUrl', 'from', 'to', 'state', 'time', 'text',
                'errorMessage', 'tag'
	);
	public static $translations = array(
		"sender" => "from",
		"receiver" => "to"
	);
        public static $needs = array(
		'id', 'from', 'to', 'state'
        );


	/**
	 * CTor to message object
	 * inherit base provide all implementation here.
         * @args -> argument object
	 */
	public function __construct($data = null /* polymorphic */)
	{ 
		$this->client = Client::get();

		return Resolver::Find($this, $data);
	}
	
	/**
	 * Where parameters
	 * are as follows
	 * @param sender -> PhoneNumber or string
	 * @param receiver -> PhoneNumber or string 
	 * @param page  -> int
	 * @param size -> int
	 */
	public function list_messages($args = array() /* polymorphic. **args */)
	{
		$args = Ensure::Input($args);
		if (!($args->has("size")))
			$args->add("size", DEFAULTS::SIZE);
		if (!($args->has("page")))
			$args->add("page", DEFAULTS::SIZE);


		$url = URIResource::Make($this->path, array());

		return new MessageCollection(new DataPacketCollection($this->client->get($url, $args->get(), TRUE)));
	}


	/**
	 * Get message by id
	 * @param message_id -> string
	 */
	public function get($message_id)
	{
		$url = URIResource::Make($this->path, array($message_id));
                $data = new DataPacket($this->client->get($url));

		return Constructor::Make($this, $data->get()); 
	}

	/* stub for property access by resolver */
        public function create()
	{
		$args = func_get_args();
		return $this->send($args);
	}

	/**
	 * Send message with
	 * additional parameters
         * important rewrite in place of
         * more polymorphic style. 
         * i.e send(from, to, message, calback)
         * and send($params)
         * @param $args -> list of valid parameters
	 */	
	public function send($args)
	{
		$data = Ensure::Input($args);
		$url = URIResource::Make($this->path);
		$message_id = Locator::Find($this->client->post($url, $data->get()));
		$data->add("id", $message_id);

		return Constructor::Make($this, $data->get());
	}
}

class MessageMulti extends GenericResource {
	private $path = "messages";

	public function __construct($messages=array())
	{
		$this->messages = $messages;

		parent::__construct();
	}

	public function list_messages($arr)
	{
		$this->messages = array();
		$this->errors = array();
		$this->done = FALSE;
	}

	/**
	 * push a message to
	 * the queue
	 * @all params -> $string to objects according to api.
	 */
	public function push_message($sender, $receiver, $text, $callback, $timeout=0)
	{
		$message = Ensure::Input(array("from" => $sender,
					        "to" => $receiver,
						"text" => $text,
						"callbackUrl" => $callback));

		$this->messages[] = $message->get();
	}

	public function execute()
	{
		if ($this->complete)
			Throw new \CatapultApiException("You\'ve already done this");

		$this->complete = TRUE;

		$msgs = $this->post_messages();
		$smsgs = array();

		foreach ($msgs as $msg) {
			$data = array_pop($this->messages);

			if (isset($msg->result->location)
			   || isset($msg->location)) {
				$data['id'] = $msg->result->location;
				$data['state'] = MESSAGE_STATES::sent;
				$smsgs[] = $msg;
			} elseif (isset($msg->result->error)
				 || isset($msg->error)) {
				$data['error_message'] = $msg->error->message;
				$data['state'] = MESSAGE_STATES::error;
				$smsgs[] = $msg;
			}
		}

		return $smsgs;
	}

	/**
	 * multiform send
	 * messages output should
	 * satisfy array
	 */
	protected function post_messages()
	{
		$url = URIResource::Make($this->path);

		$messages = $this->client->post($url, $this->messages);
	
		return $messages;
	}
}



final class RecordingCollection extends CollectionObject { 
	public function getName()
	{
		return "Recording";
	}
}

/**
 * Message container for
 * multiple returned output
 */
class MessageCollection extends CollectionObject {
	public function getName()
	{
		return "message";
	}
}

final class BridgeCollection extends AudioMixin {
       public function getName()
       {
             return "Bridge";
       }
}

final class EventCollection extends CollectionObject  {
	public function getName()
	{
		return "Event";
	}
}

final class PhoneNumbersCollection extends CollectionObject {
	public function getName()
	{
		return "PhoneNumbers";
	}
}

final class TranscriptionCollection extends CollectionObject {
	public function getName()
	{
		return "Transcription";
	}
}

final class TransactionCollection extends CollectionObject {
	public function getName()
	{
		return "Transaction";
	}
}

final class CallCollection extends CollectionObject { 
	public function getName()
	{
		return "Call";
	}
}

final class UserErrorCollection extends CollectionObject {
	public function getName()
	{
		return "UserError";
	}
}

final class MediaCollection extends CollectionObject {
	public function getName()
	{
		return "Media";
	}
}

final class GatherCollection extends CollectionObject {
	public function getName()
	{
		return "Gather";
	}
}

final class ApplicationCollection extends CollectionObject {
	public function getName()
	{
		return "Application";
	}
}

final class Transaction extends GenericResource {
	public $fields = array(
		"id"
	);

	public function __construct($data=null)
	{
		$client = Client::get();

		return Resolver::Find($this, $data);
	}
}

final class Application extends GenericResource {

	private $path = "applications";

	public static $fields = array(
	       'id', 'name',
               'incoming_call_url',
               'incoming_call_url_callback_timeout',
               'incoming_call_fallback_url',
               'incoming_sms_url',
               'incoming_sms_url_callback_timeout',
               'incoming_sms_fallback_url',
               'callback_http_method', 'auto_answer'
	);

	public static $needs = array(
		"id", "name"
	);

	/**
         * Construct the given 
	 * application
	 */
	public function __construct($data=null)
	{
		$this->client = Client::get();

		return Resolver::Find($this, $data);
	}

	/* Creates a new application
	 * with the provided set of data
	 * the parameters for data being: (borrowed from python models.py)
 	 *
	 * @param data:
		:name: A name you choose for this application
		:incoming_call_url: A URL where call events will be sent for an inbound call
		:incoming_call_url_callback_timeout: Determine how long should the platform wait for incomingCallUrl's response
		    before timing out in milliseconds.
		:incoming_call_fallback_url: The URL used to send the callback event if the request to incomingCallUrl fails.
		:incoming_sms_url: A URL where message events will be sent for an inbound SMS message.
		:incoming_sms_url_callback_timeout: Determine how long should the platform wait for incomingSmsUrl's response
		    before timing out in milliseconds.
		:incoming_sms_fallback_url: The URL used to send the callback event if the request to incomingSmsUrl fails.
		:callback_http_method: Determine if the callback event should be sent via HTTP GET or HTTP POST.
		    (If not set the default is HTTP POST).
		:auto_answer: Determines whether or not an incoming call should be automatically answered.
		    Default value is 'true'.
 	 */
	public function create($data)
	{
		$data = Ensure::Input($data);
		$url = URIResource::Make($this->path);
	
		$app_id = Locator::Find($this->client->post($url, $data->get()));

		$data->add("id", $app_id);

		return Constructor::Make($this, $data->get());
	}

	/**
         * list all your applications
         * @param data. array with page, and size
         */
	public function list_applications($data)
	{
		$data = Ensure::Input($data);
		if (!($data->has("size")))
			$data->add("size", DEFAULTS::SIZE);		
		if (!($data->has("page")))
			$data->add("page", DEFAULTS::PAGE);

		$url = URIResource::Make($this->path);
		$data = $this->client->get($url, $data->get());

		return new ApplicationCollection(new DataPacketCollection($data));
	}


	/**
         * get an application by its id
	 *
	 * @param id: full id of application
	 */
	public function get($id)
	{
		$url = URIResource::Make($this->path, array($id));

		$data = new DataPacket($this->client->get($url));

		return Constructor::Make($this, $data->get());	
	}

	/**
	 * stub for patch.
	 *
	 * @args: see patch/1
	 */
	public function update($args)
	{
		return $this->patch($args);
	}

	/**
	 * Patch the given
	 * application with new information
	 * same as update/1
         * @param data: set of application data
	 */
	public function patch($data)
	{
		$data = Ensure::Input($data);
		$url = URIResource::Make($this->path, array($this->id));
		
		$this->client->post($url, $data->get());

		return Constructor::Make($this, $data->get());
	}

	/**
	 * Delete the application
	 *
	 * @param optional by uninitialized id
	 */
	public function delete($id)
	{
		$url = URIResource::Make($this->path, array($this->id));

		$this->client->delete($url);

		return Constructor::Make($this, $data->get());
	}
} 

final class Account extends GenericResource {
	private $path = "account";
	public static $fields = array(
		"balance", "account_type"
	);

	public static $needs = array(
		"balance", "account_type"
	);

	public function __construct()
	{
		$this->client = Client::Get();

		/** directly get the account no further action needed **/

		return $this->get();
	}

	/**
	 * Get an account
	 */
	public function get($id=null)
	{
		$url = URIResource::Make($this->path);

		$data = new DataPacket($this->client->get($url));

		return Constructor::Make($this, $data->get());
	}

	/**
	 * get all the transactions
	 * from an account. Where
	 * the query can contain
	 * max_items, to_date, type, page, size
	 * 
	 * @param query: list of options
	 */
	public function get_transactions($query)
	{
		$data = Ensure::Input($query);
		$url = URIResource::Make($this->path, array("transactions"));

		$data = $this->client->get($url, $data->get());

		return new TransactionCollection(new DataPacketCollection($data));
	}
}

/* Class to get, allocate
 * phone numbers
 *
 */
final class PhoneNumbers extends GenericResource {
	private $path = "phoneNumbers";
	private $availablePath = "availableNumbers";

	public static $fields = array(
		'id', 'application', 'number', 'national_number',
		 'name', 'created_time', 'city', 'state', 'price',
		 'number_state', 'fallback_number', 'pattern_match', 'lata', 'rate_center'
	);

	public static $needs = array(
		'id', 'number', 'name'
	);

	public function __construct($data=null)
	{
		$this->client = Client::Get();

		return Resolver::Find($this, $data);
	}

	/**
	 * get your listed
	 * numbers.
	 * @param args: set of sizing options
	 */
	public function list_numbers($args)
	{
		$data = Ensure::Input($args);
		if (!($data->has("size")))
			$data->add("size", DEFAULTS::SIZE);
		if (!($data->has("page")))
			$data->add("page", DEFAULTS::PAGE);

		$url = URIResource::Make($this->path);

		$res = $this->client->get($url, $data->get());

		return new PhoneNumbersCollection(new DataPacketCollection($res));	
	}

	/**
	 * get a valid number
	 * by id
	 */
	public function get($id)
	{
		$url = URIResource::Make($this->path, array($id));	

		$data = $this->client->get($url);

		return Constructor::Make($this, $data);
	}

	/**
	 * Get the information
	 * for a given number
	 * @param valid number
	 */
	public function get_number_info($number)
	{
		return $this->get($number);
	}

	/**
         * Make the needed changes to 
	 * the PhoneNumber. Where
	 * set of params can be:
	 * application, 
	 * fallback_number,
	 *  
	 * @param data: set of valid patching options
	 */
	public function patch($data)
	{
		$app = $data['application'];
		if ($app instanceof Application)
			$data['application'] = $app->id; 

		$data = Ensure::Input($data);
		$url = URIResource::Make($this->path, array($this->id));
	
		$this->client->post($url, $data->get());	

		return Constructor::Make($this, $data->get());
	}

	/* Deletes an allocated
	 * number. this cannot be undone
	 * 
	 * @param id in place of initialized
	 */
	public function delete()
	{
		$url = URIResource::Make($this->path, array($this->id)); 

		return $this->client->delete($url);
	}

	/**
	 * stub for allocate 
         * get new numbers
	 * @param args: see allocate
	 */
	public function create($args)
	{
		return $this->allocate($args);
	}

	/**
	 * allocate a new number
	 * number must be available
	 * or warning will be thrown
	 * @param args
	 *   number, 
	 *   application (one you want to associate this number with)
	 *   fallback a fallback option if this isnt available
	 */
	public function allocate($args)
	{
		$data = Ensure::Input($args);

		$url = URIResource::Make($this->path);

		$id = Locator::Find($this->client->post($url, $data->get()));
	
		$data->add("id", $id);

		return Constructor::Make($this, $data->get());
	}

	/** validate params for availabe local number
	 * search. Rules:
	 * 1) state, zip and areaCode are mutually exclusive use only one of them per
	 *    request
	 * 2) localNumber and inLocalCallingArea only applies for searching and  order
	 *    numbers in specific areaCode
         *
         * @param args: set of arguments with above constraints
	 */
	public function validate_search_query($args)
	{
		if (array_key_exists("zip", $args) && !(array_key_exists("state", $args) || array_key_exists("areaCode", $args)))
			return;

		if (array_key_exists("state", $args) && !(array_key_exists("zip", $args) || array_key_exists("areaCode", $args)))
			return;

		if (array_key_exists("areaCode", $args) && !(array_key_exists("zip", $args) || array_key_exists("state", $args)))
			return;

		if (!(array_key_exists("areaCode", $args) && array_key_exists("zip", $args) && array_key_exists("state", $args)))
			throw new \CatapultApiException("state, zip and areaCode are mutually exclusive, you may use only one of them per request");
		if (!(array_key_exists("areaCode")))
			throw new \CatapultApiException("localNumber and inLocalCallingArea only applies '
                             'for searching numbers in specific areaCode'");
	}

	/**
	 * List the local numbers
	 * according to the provided numbers
	 * 
	 * @param params
	 */
	public function list_local($params)
	{
		$data = Ensure::Input($params);

		if (!($data->has("size")))
			$data->add("size", DEFAULTS::SIZE);
		if (!($data->has("page")))
			$data->add("page", DEFAULTS::PAGE);

		$url = URIResource::Make($this->availablePath, array("local"));

		$data = $this->client->get($url, $data->get(), true, false);

		return new PhoneNumbersCollection(new DataPacketCollection($data));
	}

	/**
	 * List toll free numbers
	 * according to the provided parameters
	 *
	 * @param set of toll free parameters
	 */
	public function list_toll_free($params)
	{
		$data = Ensure::Input($params);

		if (!($data->has("size")))
			$data->add("size", DEFAULTS::SIZE);
		if (!($data->has("page")))
			$data->add("page", DEFAULTS::PAGE);


		$url = URIResource::Make($this->availablePath, array("tollFree"));

		$data = $this->client->get($url, $data->get(), true, false);

		return new PhoneNumbersCollection(new DataPacketCollection($data));
	}

	/**
	 * Allocate numbers in batch
	 * where numbers must be local
         *
         * notes:
	 * 1. state, zip and area_code are mutually exclusive,
         *   you may use only one of them per calling list_local.
	 * 2. local_number and in_local_calling_area only applies
         *    for searching numbers in specific area_code.
	 * @param params
	 */
	public function batch_allocate_local($params)
	{
		$this->validate_search_query($params);

		$args = Ensure::Input($params);

		$url = URIResource::Make($this->availablePath, array("local"));

		$data = $this->client->post($url, $args->get(), true, false, true /* mixed uses GET parameters */);

		return new PhoneNumbersCollection(new DataPacketCollection($data));
	}

	/**
	 * TollFree version batch allocation
	 *
	 * @param params
	 */
	public function batch_allocate_tollfree($params)
	{
		$url = URIResource::Make($this->availablePath, array("tollFree"));	
		
		$args = Ensure::Input($params);

		$data = $this->client->post($url, $args->get(), true, false, true /* mixed use GET parameters */);

		return new PhoneNumbersCollection(new DataPacketCollection($data));
	}
}

/**
 * This resource provides a CNAM number info. CNAM is an acronym which stands for Caller ID Name.
 * CNAM can be used to display the calling party's name alongside the phone number, to help users easily
 * identify a caller. CNAM API allows the user to get the CNAM information of a particular number
 */
final class NumberInfo extends GenericResource {
	private $path = "phoneNumbers/numberInfo";	

	public static $fields = array(
		'name', 'number', 'created', 'updated'
	);

	public static $needs = array(
		'name', 'number'	
	);

	public function __construct($data=null)
	{
		$this->client = Client::Get();

		return Resolver::Find($this, $data);
	}

	public function get($number)
	{
		$url = URIResource::Make($this->path, array($number));

		$data = $this->client->get($url, array(), true, false);

		return Constructor::Make($this, $data);
	}
}

/* errors for application
 *
 * when using the API you will be warned
 * of errors with the HTTP calls, and client
 * side warnings. This class will contain a historic
 * list of events, warnings and errors 
 */
final class UserError extends GenericResource {
	private $path = "errors";
	public static $fields = array(
		'id', 'time', 'category', 'code', 'message', 'details', 'version', 'user'
	);
	public static $needs = array(
		'id', 'code', 'message'
	);
	public function __construct($data=null)
	{
		$this->client = Client::Get();

		return Resolver::Find($this, $data);
	}

	/**
	 * List all the errors
	 * as per the query
	 *
	 * @param query
	 */
	public function list_errors($query)
	{
		$data = Ensure::Input($query);

		if (!($data->has("size")))
			$data->add("size", DEFAULTS::SIZE);
		if (!($data->has("page")))
			$data->add("page", DEFAULTS::PAGE);

		$url = URIResource::Make($this->path);

		$data = $this->client->get($url, $data->get());

		return new UserErrorCollection(new DataPacketCollection($data));
	}

	/**
	 * get an error by its 
	 * id
	 * 
	 * @param id: real id for error
	 */
	public function get($id)
	{
		$url = URIResource::Make($this->path, array($id));

		$data = $this->client->get($id);

		return Constructor::Make($this, $data);
	}
}


?>
