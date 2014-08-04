function conf = voc_config_9090()
	conf.pascal.year = '9090';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9090/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9090/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9090/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9090/Images/%s.txt';
end