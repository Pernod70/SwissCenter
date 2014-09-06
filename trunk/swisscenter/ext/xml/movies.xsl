<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" 
xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
xmlns:xs="http://www.w3.org/2001/XMLSchema">
<xsl:template match="/sc.movie">
  <html>
  <body>
    <center>
      <h2><xsl:value-of select="title"/> (<xsl:value-of select="year"/>)</h2>
      <h4>Runtime: <xsl:value-of select="runtime"/>mins</h4>
    </center>
    <table border="1" cellpadding="5">
      <tr bgcolor="#9acd32">
        <th colspan="1">Poster</th>
        <th colspan="2">Synopsis</th>
        <th colspan="1">Certificates</th>
      </tr>
      <tr valign="top">
        <td colspan="1" style="width:25%" align="center"><img width="300" alt=""><xsl:attribute name="src"><xsl:value-of select="image"/></xsl:attribute></img></td>
        <td colspan="2" style="width:50%;text-align:justify"><xsl:value-of select="synopsis"/><br/><br/><xsl:element name="a"><xsl:attribute name="href"><xsl:value-of select="trailer"/></xsl:attribute>Watch trailer</xsl:element></td>
        <td colspan="1" style="width:25%">
          <table>
            <xsl:for-each select="certificates/certificate">
            <tr>
              <td style="width:12%;text-align:right"><xsl:value-of select="@scheme"/>: </td>
              <td style="width:12%;text-align:left"><xsl:value-of select="."/></td>
            </tr>
            </xsl:for-each>
          </table>
        </td>
      </tr>
      <tr bgcolor="#9acd32">
        <th>Actors</th>
        <th>Directors</th>
        <th>Genres</th>
        <th>Languages</th>
      </tr>
      <tr valign="top">
        <td style="width:25%">
          <table style="width:100%">
            <xsl:for-each select="actors/actor">
            <tr>
              <td style="text-align:center"><xsl:value-of select="name"/></td>
              <td style="text-align:center">
                <xsl:value-of select="character"/>
                <xsl:for-each select="characters/character">
                  <xsl:value-of select="."/><br/>
                </xsl:for-each>
              </td>
            </tr>
            </xsl:for-each>
          </table>
        </td>
        <td style="width:25%">
          <table style="width:100%">
            <xsl:for-each select="directors/director">
            <tr>
              <td style="text-align:center"><xsl:value-of select="."/></td>
            </tr>
            </xsl:for-each>
          </table>
        </td>
        <td style="width:25%">
          <table style="width:100%">
            <xsl:for-each select="genres/genre">
            <tr>
              <td style="text-align:center"><xsl:value-of select="."/></td>
            </tr>
            </xsl:for-each>
          </table>
        </td>
        <td style="width:25%">
          <table style="width:100%">
            <xsl:for-each select="languages/language">
            <tr>
              <td style="text-align:center"><xsl:value-of select="."/></td>
            </tr>
            </xsl:for-each>
          </table>
         </td>
      </tr>
    </table>
  </body>
  </html>
</xsl:template>
</xsl:stylesheet>