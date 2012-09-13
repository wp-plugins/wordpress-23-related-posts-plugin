if [ "`echo $0 | cut -c1`" = "/" ] ; then
	SCRIPT_DIR=`dirname $0`
else
	SCRIPT_FILE=`pwd`/`echo $0`
	SCRIPT_DIR=`dirname $SCRIPT_FILE`
fi

source $SCRIPT_DIR/../config.sh

FROM_DIR=$SCRIPT_DIR

for i in ${!WP_URL[*]} ; do
	THIS_WP_PLUGIN_DIR=${WP_PLUGIN_DIR[$i]}
	THIS_WP_URL=${WP_URL[$i]}

	TO_DIR=$THIS_WP_PLUGIN_DIR
	echo "Deploying plugin to $TO_DIR..."

	rm -rf $TO_DIR
	cp -r $FROM_DIR $TO_DIR
	cp -r $FROM_DIR/../static $TO_DIR/__globalstatic

	sed "s%\"WP_RP_STATIC_BASE_URL\", \".*\"%\"WP_RP_STATIC_BASE_URL\", \"`echo $THIS_WP_URL`/wp-content/plugins/wordpress-23-related-posts-plugin/__globalstatic/\"%" $TO_DIR/defaults.php > $TO_DIR/__defaults.php
	mv $TO_DIR/__defaults.php $TO_DIR/defaults.php
done
