function conf = voc_config_9074()
	conf.pascal.year = '9074';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9074/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9074/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9074/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9074/Images/%s.txt';
end