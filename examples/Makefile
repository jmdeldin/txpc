TXPC    := php ../txpc.php
SRC     := example.php
CACHE   := cache
RELEASE := releases

all:
	$(TXPC) $(SRC) $(CACHE) $(RELEASE)

clean:
	rm -f {$(RELEASE)/,$(CACHE)/}*

