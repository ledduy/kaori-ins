function conf = voc_config_9094()
	conf.pascal.year = '9094';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9094/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9094/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9094/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9094/Images/%s.txt';
end