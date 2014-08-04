function conf = voc_config_9076()
	conf.pascal.year = '9076';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9076/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9076/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9076/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9076/Images/%s.txt';
end